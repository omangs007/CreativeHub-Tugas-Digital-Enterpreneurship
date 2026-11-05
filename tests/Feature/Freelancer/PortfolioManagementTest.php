<?php

namespace Tests\Feature\Freelancer;

use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_customer_cannot_manage_portfolio(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/dashboard/portfolio')->assertForbidden();
        $this->actingAs($customer)->post('/dashboard/portfolio', [
            'title' => 'Coba',
            'image' => UploadedFile::fake()->image('a.png'),
        ])->assertForbidden();

        $this->assertDatabaseCount('portfolio_items', 0);
    }

    public function test_freelancer_can_view_portfolio_page(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        PortfolioItem::factory()->for($freelancer, 'freelancer')->create(['title' => 'Poster Festival Musik']);

        $this->actingAs($freelancer)
            ->get('/dashboard/portfolio')
            ->assertOk()
            ->assertSee('Poster Festival Musik');
    }

    public function test_freelancer_can_upload_portfolio_item(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $category = Category::factory()->create();

        $this->actingAs($freelancer)
            ->post('/dashboard/portfolio', [
                'title' => 'Map Obby Luar Angkasa',
                'description' => 'Map 30 stage.',
                'category_id' => $category->id,
                'project_url' => 'https://roblox.com/games/123',
                'image' => UploadedFile::fake()->image('Map Saya <script>.png', 1200, 800),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard/portfolio');

        $item = PortfolioItem::sole();

        $this->assertSame($freelancer->id, $item->user_id);
        $this->assertSame($category->id, $item->category_id);
        $this->assertStringStartsWith("portfolio/{$freelancer->id}/", $item->image_path);
        $this->assertStringNotContainsString('script', $item->image_path);
        Storage::disk('public')->assertExists($item->image_path);
    }

    public function test_upload_rejects_invalid_files(): void
    {
        $freelancer = User::factory()->freelancer()->create();

        $cases = [
            'pdf' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            'svg' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            'too large' => UploadedFile::fake()->image('besar.jpg')->size(2049),
        ];

        foreach ($cases as $file) {
            $this->actingAs($freelancer)
                ->post('/dashboard/portfolio', ['title' => 'Coba', 'image' => $file])
                ->assertSessionHasErrors('image');
        }

        $this->actingAs($freelancer)
            ->post('/dashboard/portfolio', ['title' => 'Tanpa gambar'])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('portfolio_items', 0);
    }

    public function test_upload_rejects_inactive_category_and_unsafe_url(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $inactive = Category::factory()->inactive()->create();

        $this->actingAs($freelancer)
            ->post('/dashboard/portfolio', [
                'title' => 'Coba',
                'category_id' => $inactive->id,
                'project_url' => 'javascript:alert(1)',
                'image' => UploadedFile::fake()->image('a.png'),
            ])
            ->assertSessionHasErrors(['category_id', 'project_url']);
    }

    public function test_portfolio_is_limited_per_freelancer(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        PortfolioItem::factory()->count(PortfolioItem::MAX_PER_FREELANCER)->for($freelancer, 'freelancer')->create();

        $this->actingAs($freelancer)
            ->post('/dashboard/portfolio', ['title' => 'Kelebihan', 'image' => UploadedFile::fake()->image('a.png')])
            ->assertSessionHasErrors('image');

        $this->assertSame(PortfolioItem::MAX_PER_FREELANCER, $freelancer->portfolioItems()->count());
    }

    public function test_owner_can_update_item_and_replace_image(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        Storage::disk('public')->put('portfolio/lama.png', 'x');
        $item = PortfolioItem::factory()->for($freelancer, 'freelancer')->create(['image_path' => 'portfolio/lama.png']);

        $this->actingAs($freelancer)->get("/dashboard/portfolio/{$item->id}/edit")->assertOk();

        $this->actingAs($freelancer)
            ->patch("/dashboard/portfolio/{$item->id}", [
                'title' => 'Judul Diperbarui',
                'image' => UploadedFile::fake()->image('baru.webp'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard/portfolio');

        $item->refresh();

        $this->assertSame('Judul Diperbarui', $item->title);
        $this->assertNotSame('portfolio/lama.png', $item->image_path);
        Storage::disk('public')->assertMissing('portfolio/lama.png');
        Storage::disk('public')->assertExists($item->image_path);
    }

    public function test_update_without_new_image_keeps_existing_image(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        $item = PortfolioItem::factory()->for($freelancer, 'freelancer')->create(['image_path' => 'portfolio/tetap.png']);

        $this->actingAs($freelancer)
            ->patch("/dashboard/portfolio/{$item->id}", ['title' => 'Hanya Judul'])
            ->assertSessionHasNoErrors();

        $this->assertSame('portfolio/tetap.png', $item->fresh()->image_path);
    }

    public function test_owner_can_delete_item_and_its_image(): void
    {
        $freelancer = User::factory()->freelancer()->create();
        Storage::disk('public')->put('portfolio/hapus.png', 'x');
        $item = PortfolioItem::factory()->for($freelancer, 'freelancer')->create(['image_path' => 'portfolio/hapus.png']);

        $this->actingAs($freelancer)
            ->delete("/dashboard/portfolio/{$item->id}")
            ->assertRedirect('/dashboard/portfolio');

        $this->assertModelMissing($item);
        Storage::disk('public')->assertMissing('portfolio/hapus.png');
    }

    public function test_other_freelancer_cannot_edit_update_or_delete_item(): void
    {
        $owner = User::factory()->freelancer()->create();
        $intruder = User::factory()->freelancer()->create();
        $item = PortfolioItem::factory()->for($owner, 'freelancer')->create(['title' => 'Milik Owner']);

        $this->actingAs($intruder)->get("/dashboard/portfolio/{$item->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->patch("/dashboard/portfolio/{$item->id}", ['title' => 'Diretas'])->assertForbidden();
        $this->actingAs($intruder)->delete("/dashboard/portfolio/{$item->id}")->assertForbidden();

        $this->assertSame('Milik Owner', $item->fresh()->title);
    }
}
