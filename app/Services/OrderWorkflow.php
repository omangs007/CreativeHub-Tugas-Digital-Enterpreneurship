<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderRevision;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Support\Rupiah;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Alur pengerjaan pesanan setelah dibayar: kirim hasil, minta revisi, selesaikan.
 * Setiap langkah memeriksa ulang pihak dan status di dalam transaksi dengan order terkunci.
 */
class OrderWorkflow
{
    public function __construct(private readonly Wallet $wallet) {}

    /**
     * @throws ValidationException
     */
    public function deliver(Order $order, User $freelancer, string $message, ?string $linkUrl, ?UploadedFile $file): OrderDelivery
    {
        $stored = $file ? $this->storeFile($order, $file) : null;

        try {
            return DB::transaction(function () use ($order, $freelancer, $message, $linkUrl, $stored) {
                $order = $this->lock($order);

                if ($order->freelancer_id !== $freelancer->id) {
                    throw ValidationException::withMessages(['order' => __('Hanya freelancer yang ditugaskan yang dapat mengirim hasil.')]);
                }

                if (! in_array($order->status, [OrderStatus::InProgress, OrderStatus::RevisionRequested], true)) {
                    throw ValidationException::withMessages(['order' => __('Hasil hanya dapat dikirim saat pesanan dikerjakan atau revisi diminta.')]);
                }

                $delivery = new OrderDelivery;
                $delivery->order_id = $order->id;
                $delivery->freelancer_id = $freelancer->id;
                $delivery->message = $message;
                $delivery->link_url = $linkUrl;
                $delivery->file_path = $stored['path'] ?? null;
                $delivery->file_name = $stored['name'] ?? null;
                $delivery->file_mime = $stored['mime'] ?? null;
                $delivery->file_size = $stored['size'] ?? null;
                $delivery->save();

                $order->transitionTo(OrderStatus::Delivered, $freelancer, __('Hasil pekerjaan dikirim.'));

                ActivityNotification::send($order->customer, __('Hasil pekerjaan dikirim'),
                    __(':name mengirim hasil untuk :title. Tinjau lalu selesaikan atau minta revisi.', ['name' => $freelancer->name, 'title' => $order->title]),
                    'orders.show', $order);

                return $delivery;
            });
        } catch (Throwable $e) {
            if ($stored) {
                Storage::disk(OrderDelivery::DISK)->delete($stored['path']);
            }

            throw $e;
        }
    }

    /**
     * @throws ValidationException
     */
    public function requestRevision(Order $order, User $customer, string $reason): OrderRevision
    {
        return DB::transaction(function () use ($order, $customer, $reason) {
            $order = $this->lock($order);
            $this->ensureCustomerReviewing($order, $customer);

            if ($order->revisions_used >= $order->revisions_allowed) {
                throw ValidationException::withMessages(['reason' => __('Jatah revisi untuk pesanan ini sudah habis.')]);
            }

            $revision = new OrderRevision;
            $revision->order_id = $order->id;
            $revision->order_delivery_id = $order->deliveries()->latest('id')->value('id');
            $revision->requested_by = $customer->id;
            $revision->reason = $reason;
            $revision->save();

            $order->revisions_used++;
            $order->transitionTo(OrderStatus::RevisionRequested, $customer, __('Revisi ke-:n diminta.', ['n' => $order->revisions_used]));

            ActivityNotification::send($order->freelancer, __('Revisi diminta'),
                __(':name meminta revisi untuk :title.', ['name' => $customer->name, 'title' => $order->title]),
                'orders.show', $order);

            return $revision;
        });
    }

    /**
     * @throws ValidationException
     */
    public function complete(Order $order, User $customer): Order
    {
        return DB::transaction(function () use ($order, $customer) {
            $order = $this->lock($order);
            $this->ensureCustomerReviewing($order, $customer);

            $order->transitionTo(OrderStatus::Completed, $customer, __('Pesanan diselesaikan oleh customer.'));

            // Customer menyetujui hasil: escrow dicairkan ke saldo tersedia freelancer.
            $released = $this->wallet->releaseForOrder($order);

            ActivityNotification::send($order->freelancer, __('Pesanan selesai'),
                $released
                    ? __(':title telah diselesaikan customer. Dana :amount masuk saldo tersedia dan dapat ditarik.', ['title' => $order->title, 'amount' => Rupiah::format($released->amount)])
                    : __(':title telah diselesaikan customer. Pendapatan Anda tercatat.', ['title' => $order->title]),
                'orders.show', $order);

            if ($order->project_id) {
                Project::whereKey($order->project_id)->update(['status' => ProjectStatus::Completed, 'updated_at' => now()]);
            }

            return $order;
        });
    }

    /**
     * Customer membatalkan pesanan yang belum dibayar. Tagihan menunggu ikut dibatalkan
     * dan proyek asal proposal ditutup.
     *
     * @throws ValidationException
     */
    public function cancelUnpaid(Order $order, User $customer): Order
    {
        return DB::transaction(function () use ($order, $customer) {
            $order = $this->lock($order);

            if ($order->customer_id !== $customer->id) {
                throw ValidationException::withMessages(['order' => __('Hanya customer pemilik pesanan yang dapat membatalkan.')]);
            }

            Payment::where('order_id', $order->id)
                ->where('status', PaymentStatus::Pending)
                ->lockForUpdate()
                ->get()
                ->each(function (Payment $payment) use ($customer) {
                    $payment->status = PaymentStatus::Cancelled;
                    $payment->save();
                    $payment->logEvent('cancelled_with_order', PaymentStatus::Pending, PaymentStatus::Cancelled, $customer);
                });

            $order->transitionTo(OrderStatus::Cancelled, $customer, __('Pesanan dibatalkan customer sebelum dibayar.'));

            ActivityNotification::send($order->freelancer, __('Pesanan dibatalkan'),
                __(':title dibatalkan customer sebelum dibayar.', ['title' => $order->title]),
                'orders.show', $order);

            if ($order->project_id) {
                Project::whereKey($order->project_id)->update(['status' => ProjectStatus::Closed, 'closed_at' => now(), 'updated_at' => now()]);
            }

            return $order;
        });
    }

    private function lock(Order $order): Order
    {
        return Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * @throws ValidationException
     */
    private function ensureCustomerReviewing(Order $order, User $customer): void
    {
        if ($order->customer_id !== $customer->id) {
            throw ValidationException::withMessages(['order' => __('Hanya customer pemilik pesanan yang dapat melakukan aksi ini.')]);
        }

        if ($order->status !== OrderStatus::Delivered) {
            throw ValidationException::withMessages(['order' => __('Aksi ini hanya tersedia setelah freelancer mengirim hasil.')]);
        }
    }

    /**
     * Simpan file dengan nama acak di disk privat; nama asli disanitasi untuk unduhan.
     *
     * @return array{path: string, name: string, mime: string, size: int}
     */
    private function storeFile(Order $order, UploadedFile $file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());
        $baseName = Str::limit(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), 80, '') ?: 'hasil';

        return [
            'path' => $file->storeAs("deliveries/{$order->id}", Str::random(40).'.'.$extension, OrderDelivery::DISK),
            'name' => $baseName.'.'.$extension,
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
        ];
    }
}
