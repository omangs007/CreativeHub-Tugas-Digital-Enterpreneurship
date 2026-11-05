--
-- PostgreSQL database dump
--

\restrict C2MeQhPEQEnsp6aMIdRvQ4P6T1v7LwFUcEf4WaGhklTZ2vscw7PzxG5zeWZNwbN

-- Dumped from database version 17.11
-- Dumped by pg_dump version 17.11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.user_roles DROP CONSTRAINT IF EXISTS user_roles_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.user_roles DROP CONSTRAINT IF EXISTS user_roles_role_id_foreign;
ALTER TABLE IF EXISTS ONLY public.services DROP CONSTRAINT IF EXISTS services_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.services DROP CONSTRAINT IF EXISTS services_moderated_by_foreign;
ALTER TABLE IF EXISTS ONLY public.services DROP CONSTRAINT IF EXISTS services_category_id_foreign;
ALTER TABLE IF EXISTS ONLY public.service_packages DROP CONSTRAINT IF EXISTS service_packages_service_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reviews DROP CONSTRAINT IF EXISTS reviews_service_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reviews DROP CONSTRAINT IF EXISTS reviews_order_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reviews DROP CONSTRAINT IF EXISTS reviews_freelancer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reviews DROP CONSTRAINT IF EXISTS reviews_customer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.proposals DROP CONSTRAINT IF EXISTS proposals_project_id_foreign;
ALTER TABLE IF EXISTS ONLY public.proposals DROP CONSTRAINT IF EXISTS proposals_freelancer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.projects DROP CONSTRAINT IF EXISTS projects_customer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.projects DROP CONSTRAINT IF EXISTS projects_category_id_foreign;
ALTER TABLE IF EXISTS ONLY public.portfolio_items DROP CONSTRAINT IF EXISTS portfolio_items_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.portfolio_items DROP CONSTRAINT IF EXISTS portfolio_items_category_id_foreign;
ALTER TABLE IF EXISTS ONLY public.payments DROP CONSTRAINT IF EXISTS payments_order_id_foreign;
ALTER TABLE IF EXISTS ONLY public.payments DROP CONSTRAINT IF EXISTS payments_customer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.payment_events DROP CONSTRAINT IF EXISTS payment_events_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.payment_events DROP CONSTRAINT IF EXISTS payment_events_payment_id_foreign;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_service_package_id_foreign;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_service_id_foreign;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_proposal_id_foreign;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_project_id_foreign;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_freelancer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_customer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.order_status_histories DROP CONSTRAINT IF EXISTS order_status_histories_order_id_foreign;
ALTER TABLE IF EXISTS ONLY public.order_status_histories DROP CONSTRAINT IF EXISTS order_status_histories_changed_by_foreign;
ALTER TABLE IF EXISTS ONLY public.order_revisions DROP CONSTRAINT IF EXISTS order_revisions_requested_by_foreign;
ALTER TABLE IF EXISTS ONLY public.order_revisions DROP CONSTRAINT IF EXISTS order_revisions_order_id_foreign;
ALTER TABLE IF EXISTS ONLY public.order_revisions DROP CONSTRAINT IF EXISTS order_revisions_order_delivery_id_foreign;
ALTER TABLE IF EXISTS ONLY public.order_deliveries DROP CONSTRAINT IF EXISTS order_deliveries_order_id_foreign;
ALTER TABLE IF EXISTS ONLY public.order_deliveries DROP CONSTRAINT IF EXISTS order_deliveries_freelancer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.messages DROP CONSTRAINT IF EXISTS messages_sender_id_foreign;
ALTER TABLE IF EXISTS ONLY public.messages DROP CONSTRAINT IF EXISTS messages_conversation_id_foreign;
ALTER TABLE IF EXISTS ONLY public.freelancer_profiles DROP CONSTRAINT IF EXISTS freelancer_profiles_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.conversations DROP CONSTRAINT IF EXISTS conversations_proposal_id_foreign;
ALTER TABLE IF EXISTS ONLY public.conversations DROP CONSTRAINT IF EXISTS conversations_order_id_foreign;
ALTER TABLE IF EXISTS ONLY public.conversations DROP CONSTRAINT IF EXISTS conversations_freelancer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.conversations DROP CONSTRAINT IF EXISTS conversations_customer_id_foreign;
ALTER TABLE IF EXISTS ONLY public.audit_logs DROP CONSTRAINT IF EXISTS audit_logs_user_id_foreign;
DROP INDEX IF EXISTS public.user_roles_role_id_index;
DROP INDEX IF EXISTS public.sessions_user_id_index;
DROP INDEX IF EXISTS public.sessions_last_activity_index;
DROP INDEX IF EXISTS public.services_user_id_index;
DROP INDEX IF EXISTS public.services_status_category_id_index;
DROP INDEX IF EXISTS public.service_packages_service_id_is_active_index;
DROP INDEX IF EXISTS public.reviews_service_id_is_visible_index;
DROP INDEX IF EXISTS public.reviews_freelancer_id_is_visible_index;
DROP INDEX IF EXISTS public.proposals_project_id_status_index;
DROP INDEX IF EXISTS public.proposals_one_active_per_freelancer;
DROP INDEX IF EXISTS public.proposals_one_accepted_per_project;
DROP INDEX IF EXISTS public.proposals_freelancer_id_index;
DROP INDEX IF EXISTS public.projects_status_category_id_index;
DROP INDEX IF EXISTS public.projects_customer_id_index;
DROP INDEX IF EXISTS public.portfolio_items_user_id_created_at_index;
DROP INDEX IF EXISTS public.payments_status_paid_at_index;
DROP INDEX IF EXISTS public.payments_one_success_per_order;
DROP INDEX IF EXISTS public.payments_one_pending_per_order;
DROP INDEX IF EXISTS public.payments_customer_id_index;
DROP INDEX IF EXISTS public.payments_created_at_index;
DROP INDEX IF EXISTS public.payment_events_payment_id_created_at_index;
DROP INDEX IF EXISTS public.orders_freelancer_id_status_index;
DROP INDEX IF EXISTS public.orders_customer_id_status_index;
DROP INDEX IF EXISTS public.orders_created_at_index;
DROP INDEX IF EXISTS public.order_status_histories_order_id_created_at_index;
DROP INDEX IF EXISTS public.order_revisions_order_id_created_at_index;
DROP INDEX IF EXISTS public.order_deliveries_order_id_created_at_index;
DROP INDEX IF EXISTS public.notifications_notifiable_type_notifiable_id_index;
DROP INDEX IF EXISTS public.messages_conversation_id_id_index;
DROP INDEX IF EXISTS public.jobs_queue_index;
DROP INDEX IF EXISTS public.freelancer_profiles_availability_index;
DROP INDEX IF EXISTS public.conversations_freelancer_id_last_message_at_index;
DROP INDEX IF EXISTS public.conversations_customer_id_last_message_at_index;
DROP INDEX IF EXISTS public.categories_is_active_sort_order_index;
DROP INDEX IF EXISTS public.cache_locks_expiration_index;
DROP INDEX IF EXISTS public.cache_expiration_index;
DROP INDEX IF EXISTS public.audit_logs_created_at_index;
DROP INDEX IF EXISTS public.audit_logs_auditable_type_auditable_id_index;
DROP INDEX IF EXISTS public.audit_logs_action_created_at_index;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_email_unique;
ALTER TABLE IF EXISTS ONLY public.user_roles DROP CONSTRAINT IF EXISTS user_roles_pkey;
ALTER TABLE IF EXISTS ONLY public.settings DROP CONSTRAINT IF EXISTS settings_pkey;
ALTER TABLE IF EXISTS ONLY public.sessions DROP CONSTRAINT IF EXISTS sessions_pkey;
ALTER TABLE IF EXISTS ONLY public.services DROP CONSTRAINT IF EXISTS services_slug_unique;
ALTER TABLE IF EXISTS ONLY public.services DROP CONSTRAINT IF EXISTS services_pkey;
ALTER TABLE IF EXISTS ONLY public.service_packages DROP CONSTRAINT IF EXISTS service_packages_service_id_tier_unique;
ALTER TABLE IF EXISTS ONLY public.service_packages DROP CONSTRAINT IF EXISTS service_packages_pkey;
ALTER TABLE IF EXISTS ONLY public.roles DROP CONSTRAINT IF EXISTS roles_pkey;
ALTER TABLE IF EXISTS ONLY public.roles DROP CONSTRAINT IF EXISTS roles_name_unique;
ALTER TABLE IF EXISTS ONLY public.reviews DROP CONSTRAINT IF EXISTS reviews_pkey;
ALTER TABLE IF EXISTS ONLY public.reviews DROP CONSTRAINT IF EXISTS reviews_order_id_unique;
ALTER TABLE IF EXISTS ONLY public.proposals DROP CONSTRAINT IF EXISTS proposals_pkey;
ALTER TABLE IF EXISTS ONLY public.projects DROP CONSTRAINT IF EXISTS projects_pkey;
ALTER TABLE IF EXISTS ONLY public.portfolio_items DROP CONSTRAINT IF EXISTS portfolio_items_pkey;
ALTER TABLE IF EXISTS ONLY public.payments DROP CONSTRAINT IF EXISTS payments_transaction_code_unique;
ALTER TABLE IF EXISTS ONLY public.payments DROP CONSTRAINT IF EXISTS payments_pkey;
ALTER TABLE IF EXISTS ONLY public.payment_events DROP CONSTRAINT IF EXISTS payment_events_pkey;
ALTER TABLE IF EXISTS ONLY public.password_reset_tokens DROP CONSTRAINT IF EXISTS password_reset_tokens_pkey;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_proposal_id_unique;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_pkey;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_order_number_unique;
ALTER TABLE IF EXISTS ONLY public.order_status_histories DROP CONSTRAINT IF EXISTS order_status_histories_pkey;
ALTER TABLE IF EXISTS ONLY public.order_revisions DROP CONSTRAINT IF EXISTS order_revisions_pkey;
ALTER TABLE IF EXISTS ONLY public.order_deliveries DROP CONSTRAINT IF EXISTS order_deliveries_pkey;
ALTER TABLE IF EXISTS ONLY public.notifications DROP CONSTRAINT IF EXISTS notifications_pkey;
ALTER TABLE IF EXISTS ONLY public.migrations DROP CONSTRAINT IF EXISTS migrations_pkey;
ALTER TABLE IF EXISTS ONLY public.messages DROP CONSTRAINT IF EXISTS messages_pkey;
ALTER TABLE IF EXISTS ONLY public.jobs DROP CONSTRAINT IF EXISTS jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.job_batches DROP CONSTRAINT IF EXISTS job_batches_pkey;
ALTER TABLE IF EXISTS ONLY public.freelancer_profiles DROP CONSTRAINT IF EXISTS freelancer_profiles_user_id_unique;
ALTER TABLE IF EXISTS ONLY public.freelancer_profiles DROP CONSTRAINT IF EXISTS freelancer_profiles_pkey;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_uuid_unique;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.conversations DROP CONSTRAINT IF EXISTS conversations_proposal_id_unique;
ALTER TABLE IF EXISTS ONLY public.conversations DROP CONSTRAINT IF EXISTS conversations_pkey;
ALTER TABLE IF EXISTS ONLY public.conversations DROP CONSTRAINT IF EXISTS conversations_order_id_unique;
ALTER TABLE IF EXISTS ONLY public.categories DROP CONSTRAINT IF EXISTS categories_slug_unique;
ALTER TABLE IF EXISTS ONLY public.categories DROP CONSTRAINT IF EXISTS categories_pkey;
ALTER TABLE IF EXISTS ONLY public.cache DROP CONSTRAINT IF EXISTS cache_pkey;
ALTER TABLE IF EXISTS ONLY public.cache_locks DROP CONSTRAINT IF EXISTS cache_locks_pkey;
ALTER TABLE IF EXISTS ONLY public.audit_logs DROP CONSTRAINT IF EXISTS audit_logs_pkey;
ALTER TABLE IF EXISTS public.users ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.services ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.service_packages ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.roles ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.reviews ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.proposals ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.projects ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.portfolio_items ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.payments ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.payment_events ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.orders ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.order_status_histories ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.order_revisions ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.order_deliveries ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.migrations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.messages ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.freelancer_profiles ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.failed_jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.conversations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.categories ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.audit_logs ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.users_id_seq;
DROP TABLE IF EXISTS public.users;
DROP TABLE IF EXISTS public.user_roles;
DROP TABLE IF EXISTS public.settings;
DROP TABLE IF EXISTS public.sessions;
DROP SEQUENCE IF EXISTS public.services_id_seq;
DROP TABLE IF EXISTS public.services;
DROP SEQUENCE IF EXISTS public.service_packages_id_seq;
DROP TABLE IF EXISTS public.service_packages;
DROP SEQUENCE IF EXISTS public.roles_id_seq;
DROP TABLE IF EXISTS public.roles;
DROP SEQUENCE IF EXISTS public.reviews_id_seq;
DROP TABLE IF EXISTS public.reviews;
DROP SEQUENCE IF EXISTS public.proposals_id_seq;
DROP TABLE IF EXISTS public.proposals;
DROP SEQUENCE IF EXISTS public.projects_id_seq;
DROP TABLE IF EXISTS public.projects;
DROP SEQUENCE IF EXISTS public.portfolio_items_id_seq;
DROP TABLE IF EXISTS public.portfolio_items;
DROP SEQUENCE IF EXISTS public.payments_id_seq;
DROP TABLE IF EXISTS public.payments;
DROP SEQUENCE IF EXISTS public.payment_events_id_seq;
DROP TABLE IF EXISTS public.payment_events;
DROP TABLE IF EXISTS public.password_reset_tokens;
DROP SEQUENCE IF EXISTS public.orders_id_seq;
DROP TABLE IF EXISTS public.orders;
DROP SEQUENCE IF EXISTS public.order_status_histories_id_seq;
DROP TABLE IF EXISTS public.order_status_histories;
DROP SEQUENCE IF EXISTS public.order_revisions_id_seq;
DROP TABLE IF EXISTS public.order_revisions;
DROP SEQUENCE IF EXISTS public.order_deliveries_id_seq;
DROP TABLE IF EXISTS public.order_deliveries;
DROP TABLE IF EXISTS public.notifications;
DROP SEQUENCE IF EXISTS public.migrations_id_seq;
DROP TABLE IF EXISTS public.migrations;
DROP SEQUENCE IF EXISTS public.messages_id_seq;
DROP TABLE IF EXISTS public.messages;
DROP SEQUENCE IF EXISTS public.jobs_id_seq;
DROP TABLE IF EXISTS public.jobs;
DROP TABLE IF EXISTS public.job_batches;
DROP SEQUENCE IF EXISTS public.freelancer_profiles_id_seq;
DROP TABLE IF EXISTS public.freelancer_profiles;
DROP SEQUENCE IF EXISTS public.failed_jobs_id_seq;
DROP TABLE IF EXISTS public.failed_jobs;
DROP SEQUENCE IF EXISTS public.conversations_id_seq;
DROP TABLE IF EXISTS public.conversations;
DROP SEQUENCE IF EXISTS public.categories_id_seq;
DROP TABLE IF EXISTS public.categories;
DROP TABLE IF EXISTS public.cache_locks;
DROP TABLE IF EXISTS public.cache;
DROP SEQUENCE IF EXISTS public.audit_logs_id_seq;
DROP TABLE IF EXISTS public.audit_logs;
SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint,
    action character varying(100) NOT NULL,
    auditable_type character varying(255),
    auditable_id bigint,
    old_values jsonb,
    new_values jsonb,
    ip_address character varying(45),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(120) NOT NULL,
    description text,
    icon character varying(50),
    is_active boolean DEFAULT true NOT NULL,
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: conversations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.conversations (
    id bigint NOT NULL,
    order_id bigint,
    proposal_id bigint,
    customer_id bigint NOT NULL,
    freelancer_id bigint NOT NULL,
    last_message_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT conversations_distinct_parties_check CHECK ((customer_id <> freelancer_id)),
    CONSTRAINT conversations_single_subject_check CHECK (((order_id IS NULL) <> (proposal_id IS NULL)))
);


--
-- Name: conversations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.conversations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: conversations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.conversations_id_seq OWNED BY public.conversations.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: freelancer_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.freelancer_profiles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    headline character varying(120),
    bio text,
    skills jsonb,
    availability character varying(20) DEFAULT 'available'::character varying NOT NULL,
    portfolio_url character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: freelancer_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.freelancer_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: freelancer_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.freelancer_profiles_id_seq OWNED BY public.freelancer_profiles.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.messages (
    id bigint NOT NULL,
    conversation_id bigint NOT NULL,
    sender_id bigint NOT NULL,
    body text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: messages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.messages_id_seq OWNED BY public.messages.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: order_deliveries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_deliveries (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    freelancer_id bigint NOT NULL,
    message text NOT NULL,
    link_url character varying(500),
    file_path character varying(255),
    file_name character varying(255),
    file_mime character varying(120),
    file_size integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT order_deliveries_content_check CHECK (((link_url IS NOT NULL) OR (file_path IS NOT NULL)))
);


--
-- Name: order_deliveries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_deliveries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_deliveries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_deliveries_id_seq OWNED BY public.order_deliveries.id;


--
-- Name: order_revisions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_revisions (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    order_delivery_id bigint,
    requested_by bigint NOT NULL,
    reason text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: order_revisions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_revisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_revisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_revisions_id_seq OWNED BY public.order_revisions.id;


--
-- Name: order_status_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.order_status_histories (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    from_status character varying(30),
    to_status character varying(30) NOT NULL,
    changed_by bigint,
    note text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: order_status_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.order_status_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: order_status_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.order_status_histories_id_seq OWNED BY public.order_status_histories.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orders (
    id bigint NOT NULL,
    order_number character varying(30) NOT NULL,
    customer_id bigint NOT NULL,
    freelancer_id bigint NOT NULL,
    service_id bigint,
    service_package_id bigint,
    project_id bigint,
    proposal_id bigint,
    title character varying(150) NOT NULL,
    package_tier character varying(20),
    package_name character varying(100),
    scope_description text NOT NULL,
    delivery_days smallint NOT NULL,
    revisions_allowed smallint DEFAULT '0'::smallint NOT NULL,
    revisions_used smallint DEFAULT '0'::smallint NOT NULL,
    currency character(3) DEFAULT 'IDR'::bpchar NOT NULL,
    subtotal_amount bigint NOT NULL,
    commission_rate_bps integer NOT NULL,
    commission_amount bigint NOT NULL,
    freelancer_amount bigint NOT NULL,
    total_amount bigint NOT NULL,
    status character varying(30) DEFAULT 'pending_payment'::character varying NOT NULL,
    paid_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    cancelled_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT orders_amounts_check CHECK (((total_amount > 0) AND (total_amount = subtotal_amount) AND (total_amount = (commission_amount + freelancer_amount)))),
    CONSTRAINT orders_distinct_parties_check CHECK ((customer_id <> freelancer_id)),
    CONSTRAINT orders_revisions_check CHECK ((revisions_used <= revisions_allowed)),
    CONSTRAINT orders_single_source_check CHECK ((((service_package_id IS NOT NULL) AND (service_id IS NOT NULL) AND (proposal_id IS NULL) AND (project_id IS NULL)) OR ((proposal_id IS NOT NULL) AND (project_id IS NOT NULL) AND (service_package_id IS NULL) AND (service_id IS NULL)))),
    CONSTRAINT orders_status_check CHECK (((status)::text = ANY (ARRAY[('pending_payment'::character varying)::text, ('paid'::character varying)::text, ('in_progress'::character varying)::text, ('delivered'::character varying)::text, ('revision_requested'::character varying)::text, ('completed'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: payment_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_events (
    id bigint NOT NULL,
    payment_id bigint NOT NULL,
    event character varying(50) NOT NULL,
    from_status character varying(20),
    to_status character varying(20),
    user_id bigint,
    payload jsonb,
    ip_address character varying(45),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: payment_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_events_id_seq OWNED BY public.payment_events.id;


--
-- Name: payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payments (
    id bigint NOT NULL,
    transaction_code character varying(40) NOT NULL,
    order_id bigint NOT NULL,
    customer_id bigint NOT NULL,
    method character varying(30) NOT NULL,
    provider character varying(30) DEFAULT 'dummy'::character varying NOT NULL,
    provider_reference character varying(100),
    currency character(3) DEFAULT 'IDR'::bpchar NOT NULL,
    amount bigint NOT NULL,
    commission_amount bigint NOT NULL,
    freelancer_amount bigint NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    expires_at timestamp(0) without time zone,
    paid_at timestamp(0) without time zone,
    failed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT payments_amount_check CHECK (((amount > 0) AND (amount = (commission_amount + freelancer_amount)))),
    CONSTRAINT payments_method_check CHECK (((method)::text = ANY (ARRAY[('qris'::character varying)::text, ('virtual_account'::character varying)::text, ('ewallet'::character varying)::text]))),
    CONSTRAINT payments_paid_at_check CHECK ((((status)::text <> 'success'::text) OR (paid_at IS NOT NULL))),
    CONSTRAINT payments_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('success'::character varying)::text, ('failed'::character varying)::text, ('expired'::character varying)::text, ('cancelled'::character varying)::text])))
);


--
-- Name: payments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payments_id_seq OWNED BY public.payments.id;


--
-- Name: portfolio_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.portfolio_items (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    category_id bigint,
    title character varying(120) NOT NULL,
    description text,
    image_path character varying(255) NOT NULL,
    project_url character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_visible boolean DEFAULT true NOT NULL
);


--
-- Name: portfolio_items_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.portfolio_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: portfolio_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.portfolio_items_id_seq OWNED BY public.portfolio_items.id;


--
-- Name: projects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.projects (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    category_id bigint NOT NULL,
    title character varying(150) NOT NULL,
    description text NOT NULL,
    budget_min bigint,
    budget_max bigint,
    deadline date,
    status character varying(20) DEFAULT 'open'::character varying NOT NULL,
    closed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT projects_budget_check CHECK (((budget_min IS NULL) OR (budget_max IS NULL) OR (budget_min <= budget_max))),
    CONSTRAINT projects_status_check CHECK (((status)::text = ANY (ARRAY[('open'::character varying)::text, ('in_progress'::character varying)::text, ('completed'::character varying)::text, ('closed'::character varying)::text])))
);


--
-- Name: projects_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.projects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: projects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.projects_id_seq OWNED BY public.projects.id;


--
-- Name: proposals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.proposals (
    id bigint NOT NULL,
    project_id bigint NOT NULL,
    freelancer_id bigint NOT NULL,
    price bigint NOT NULL,
    delivery_days smallint NOT NULL,
    revisions smallint DEFAULT '0'::smallint NOT NULL,
    message text NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT proposals_delivery_days_check CHECK ((delivery_days > 0)),
    CONSTRAINT proposals_price_check CHECK ((price > 0)),
    CONSTRAINT proposals_status_check CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('accepted'::character varying)::text, ('rejected'::character varying)::text, ('withdrawn'::character varying)::text])))
);


--
-- Name: proposals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.proposals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: proposals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.proposals_id_seq OWNED BY public.proposals.id;


--
-- Name: reviews; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reviews (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    service_id bigint,
    customer_id bigint NOT NULL,
    freelancer_id bigint NOT NULL,
    rating smallint NOT NULL,
    comment text,
    is_visible boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT reviews_rating_check CHECK (((rating >= 1) AND (rating <= 5)))
);


--
-- Name: reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reviews_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reviews_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reviews_id_seq OWNED BY public.reviews.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    label character varying(100) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: service_packages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.service_packages (
    id bigint NOT NULL,
    service_id bigint NOT NULL,
    tier character varying(20) NOT NULL,
    name character varying(100) NOT NULL,
    description text NOT NULL,
    price bigint NOT NULL,
    delivery_days smallint NOT NULL,
    revisions smallint DEFAULT '0'::smallint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT service_packages_delivery_days_check CHECK ((delivery_days > 0)),
    CONSTRAINT service_packages_price_check CHECK ((price > 0)),
    CONSTRAINT service_packages_tier_check CHECK (((tier)::text = ANY (ARRAY[('basic'::character varying)::text, ('standard'::character varying)::text, ('premium'::character varying)::text])))
);


--
-- Name: service_packages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.service_packages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: service_packages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.service_packages_id_seq OWNED BY public.service_packages.id;


--
-- Name: services; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.services (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    category_id bigint NOT NULL,
    title character varying(150) NOT NULL,
    slug character varying(180) NOT NULL,
    description text NOT NULL,
    thumbnail_path character varying(255),
    status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    published_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    rejection_reason text,
    moderated_by bigint,
    moderated_at timestamp(0) without time zone,
    CONSTRAINT services_status_check CHECK (((status)::text = ANY (ARRAY[('draft'::character varying)::text, ('pending_review'::character varying)::text, ('published'::character varying)::text, ('rejected'::character varying)::text])))
);


--
-- Name: services_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.services_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: services_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.services_id_seq OWNED BY public.services.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    key character varying(100) NOT NULL,
    value text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_roles (
    user_id bigint NOT NULL,
    role_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    avatar_path character varying(255)
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: conversations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations ALTER COLUMN id SET DEFAULT nextval('public.conversations_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: freelancer_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freelancer_profiles ALTER COLUMN id SET DEFAULT nextval('public.freelancer_profiles_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: messages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages ALTER COLUMN id SET DEFAULT nextval('public.messages_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: order_deliveries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_deliveries ALTER COLUMN id SET DEFAULT nextval('public.order_deliveries_id_seq'::regclass);


--
-- Name: order_revisions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_revisions ALTER COLUMN id SET DEFAULT nextval('public.order_revisions_id_seq'::regclass);


--
-- Name: order_status_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories ALTER COLUMN id SET DEFAULT nextval('public.order_status_histories_id_seq'::regclass);


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: payment_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_events ALTER COLUMN id SET DEFAULT nextval('public.payment_events_id_seq'::regclass);


--
-- Name: payments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments ALTER COLUMN id SET DEFAULT nextval('public.payments_id_seq'::regclass);


--
-- Name: portfolio_items id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portfolio_items ALTER COLUMN id SET DEFAULT nextval('public.portfolio_items_id_seq'::regclass);


--
-- Name: projects id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects ALTER COLUMN id SET DEFAULT nextval('public.projects_id_seq'::regclass);


--
-- Name: proposals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposals ALTER COLUMN id SET DEFAULT nextval('public.proposals_id_seq'::regclass);


--
-- Name: reviews id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews ALTER COLUMN id SET DEFAULT nextval('public.reviews_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: service_packages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_packages ALTER COLUMN id SET DEFAULT nextval('public.service_packages_id_seq'::regclass);


--
-- Name: services id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services ALTER COLUMN id SET DEFAULT nextval('public.services_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.audit_logs (id, user_id, action, auditable_type, auditable_id, old_values, new_values, ip_address, created_at) FROM stdin;
1	10	user.updated_by_admin	App\\Models\\User	1	{"name": "Raka Pratama", "email": "disamarkan@contoh.creativehub.test"}	{"name": "Robby Fatkhurozi", "email": "disamarkan@contoh.creativehub.test"}	\N	2026-11-05 14:06:42
2	10	user.updated_by_admin	App\\Models\\User	1	\N	{"avatar": "diganti"}	\N	2026-11-05 14:07:23
3	\N	user.updated_by_admin	App\\Models\\User	19	\N	{"avatar": "diganti"}	\N	2026-11-05 14:11:32
4	10	user.updated_by_admin	App\\Models\\User	3	\N	{"avatar": "diganti"}	\N	2026-11-05 14:28:52
5	10	user.updated_by_admin	App\\Models\\User	3	{"name": "Dimas Saputra", "email": "disamarkan@contoh.creativehub.test"}	{"name": "Abdul Rachman", "email": "disamarkan@contoh.creativehub.test"}	\N	2026-11-05 14:29:42
6	10	user.updated_by_admin	App\\Models\\User	2	{"email": "disamarkan@contoh.creativehub.test"}	{"email": "disamarkan@contoh.creativehub.test"}	\N	2026-11-05 14:30:04
7	10	user.updated_by_admin	App\\Models\\User	1	{"email": "disamarkan@contoh.creativehub.test"}	{"email": "disamarkan@contoh.creativehub.test"}	\N	2026-11-05 14:30:31
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.categories (id, name, slug, description, icon, is_active, sort_order, created_at, updated_at) FROM stdin;
1	Roblox Map	roblox-map	Desain map, obby, dan dunia game Roblox siap main.	gamepad	t	1	2026-11-05 11:01:12	2026-11-05 11:01:12
2	Website Development	website-development	Landing page, company profile, hingga aplikasi web.	code	t	2	2026-11-05 11:01:12	2026-11-05 11:01:12
3	Poster & Graphic Design	poster-graphic-design	Poster, banner, logo, dan materi visual untuk promosi.	palette	t	3	2026-11-05 11:01:12	2026-11-05 11:01:12
\.


--
-- Data for Name: conversations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.conversations (id, order_id, proposal_id, customer_id, freelancer_id, last_message_at, created_at, updated_at) FROM stdin;
3	\N	3	13	2	\N	2026-11-05 14:02:50	2026-11-05 14:02:50
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: freelancer_profiles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.freelancer_profiles (id, user_id, headline, bio, skills, availability, portfolio_url, created_at, updated_at) FROM stdin;
1	1	Roblox Map Builder & Scripter	Membangun map Roblox sejak 2021.\nFokus pada obby, tycoon, dan roleplay dengan performa ringan untuk perangkat mobile.	["Roblox Studio", "Luau", "Blender"]	available	\N	2026-11-05 11:01:12	2026-11-05 11:01:12
2	2	Laravel & Tailwind Web Developer	Web developer yang membantu UMKM dan organisasi kampus tampil profesional secara online.	["Laravel", "Tailwind CSS", "PostgreSQL"]	available	\N	2026-11-05 11:01:13	2026-11-05 11:01:13
3	3	Graphic Designer — Poster & Social Media	Desainer grafis untuk event kampus, brand lokal, dan kebutuhan promosi media sosial.	["Adobe Illustrator", "Photoshop", "Figma"]	available	\N	2026-11-05 11:01:13	2026-11-05 11:01:13
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: messages; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.messages (id, conversation_id, sender_id, body, read_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_11_05_000001_create_roles_tables	1
5	2026_11_05_000002_create_freelancer_profiles_table	1
6	2026_11_05_000003_create_categories_table	1
7	2026_11_05_000004_create_services_table	1
8	2026_11_05_000005_create_service_packages_table	1
9	2026_11_05_000006_create_portfolio_items_table	2
10	2026_11_05_000007_add_moderation_columns_to_services_table	3
11	2026_11_05_000008_create_audit_logs_table	3
12	2026_11_05_000009_create_projects_table	4
13	2026_11_05_000010_create_proposals_table	5
14	2026_11_05_000011_create_orders_table	5
15	2026_11_05_000012_create_payments_table	6
16	2026_11_05_000013_create_order_deliveries_and_revisions_tables	7
17	2026_11_05_000014_create_conversations_and_reviews_tables	8
18	2026_11_05_000015_add_admin_management_columns	9
19	2026_11_05_124459_create_notifications_table	10
20	2026_11_05_000016_add_avatar_path_to_users_table	11
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: order_deliveries; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.order_deliveries (id, order_id, freelancer_id, message, link_url, file_path, file_name, file_mime, file_size, created_at, updated_at) FROM stdin;
1	8	1	Map 50 stage sudah selesai beserta gamepass skip stage.	https://www.roblox.com/	\N	\N	\N	\N	2026-10-20 11:15:00	2026-10-20 11:15:00
2	10	3	Poster A4 siap cetak dan versi Instagram.	https://drive.google.com/	\N	\N	\N	\N	2026-11-02 10:30:00	2026-11-02 10:30:00
\.


--
-- Data for Name: order_revisions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.order_revisions (id, order_id, order_delivery_id, requested_by, reason, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: order_status_histories; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.order_status_histories (id, order_id, from_status, to_status, changed_by, note, created_at) FROM stdin;
16	8	\N	pending_payment	11	Order dibuat dari paket Premium.	2026-10-20 10:15:00
17	8	pending_payment	paid	11	Pembayaran TRX-20261105-9ITVONE9 berhasil (simulasi).	2026-10-20 10:25:00
18	8	paid	in_progress	\N	Freelancer dapat mulai mengerjakan pesanan.	2026-10-20 10:35:00
19	8	in_progress	delivered	1	Hasil pekerjaan dikirim.	2026-10-20 10:45:00
20	8	delivered	completed	11	Pesanan diselesaikan oleh customer.	2026-10-20 10:55:00
21	9	\N	pending_payment	12	Order dibuat dari paket Standard.	2026-10-26 14:05:00
22	9	pending_payment	paid	12	Pembayaran TRX-20261105-3HUSNLEB berhasil (simulasi).	2026-10-26 14:15:00
23	9	paid	in_progress	\N	Freelancer dapat mulai mengerjakan pesanan.	2026-10-26 14:25:00
24	10	\N	pending_payment	13	Order dibuat dari paket Basic.	2026-11-02 09:30:00
25	10	pending_payment	paid	13	Pembayaran TRX-20261105-ZB2EIYTW berhasil (simulasi).	2026-11-02 09:40:00
26	10	paid	in_progress	\N	Freelancer dapat mulai mengerjakan pesanan.	2026-11-02 09:50:00
27	10	in_progress	delivered	3	Hasil pekerjaan dikirim.	2026-11-02 10:00:00
28	11	\N	pending_payment	11	Order dibuat dari paket Basic.	2026-11-03 19:45:00
29	12	\N	pending_payment	12	Order dibuat dari paket Basic.	2026-10-31 11:00:00
30	13	\N	pending_payment	13	Order dibuat dari paket Basic.	2026-10-29 16:20:00
31	13	pending_payment	cancelled	13	Pesanan dibatalkan customer sebelum dibayar.	2026-10-29 16:30:00
32	14	\N	pending_payment	12	Order dibuat dari penerimaan penawaran.	2026-11-04 08:10:00
33	14	pending_payment	paid	12	Pembayaran TRX-20261105-WEAYPDLT berhasil (simulasi).	2026-11-04 08:20:00
34	14	paid	in_progress	\N	Freelancer dapat mulai mengerjakan pesanan.	2026-11-04 08:30:00
\.


--
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.orders (id, order_number, customer_id, freelancer_id, service_id, service_package_id, project_id, proposal_id, title, package_tier, package_name, scope_description, delivery_days, revisions_allowed, revisions_used, currency, subtotal_amount, commission_rate_bps, commission_amount, freelancer_amount, total_amount, status, paid_at, delivered_at, completed_at, cancelled_at, created_at, updated_at) FROM stdin;
8	ORD-261105-OMKZEM	11	1	1	3	\N	\N	Desain Map Obby Roblox Custom	premium	Premium	50 stage, sistem gamepass skip stage, dan musik latar.\n\nCatatan customer:\nTema luar angkasa, warna ungu.	14	4	0	IDR	750000	1000	75000	675000	750000	completed	2026-10-20 10:25:00	2026-10-20 11:15:00	2026-10-20 11:45:00	\N	2026-10-20 10:15:00	2026-10-20 11:45:00
9	ORD-261105-YFUXTN	12	2	3	8	\N	\N	Landing Page Responsif untuk UMKM	standard	Standard	Hingga 7 section, formulir kontak, dan optimasi SEO dasar.\n\nCatatan customer:\nUntuk toko kue rumahan, warna pastel.	7	2	0	IDR	650000	1000	65000	585000	650000	in_progress	2026-10-26 14:15:00	\N	\N	\N	2026-10-26 14:05:00	2026-10-26 15:35:00
10	ORD-261105-LS8BYR	13	3	5	13	\N	\N	Desain Poster Event & Promosi	basic	Basic	Satu desain poster ukuran A4 atau feed Instagram.\n\nCatatan customer:\nPoster lomba futsal antar kelas.	3	1	0	IDR	75000	1000	7500	67500	75000	delivered	2026-11-02 09:40:00	2026-11-02 10:30:00	\N	\N	2026-11-02 09:30:00	2026-11-02 11:00:00
11	ORD-261105-JK0NIU	11	3	6	16	\N	\N	Paket Desain Feed Instagram	basic	Basic	Tiga desain feed dengan template yang bisa dipakai ulang.	4	1	0	IDR	120000	1000	12000	108000	120000	pending_payment	\N	\N	\N	\N	2026-11-03 19:45:00	2026-11-03 21:15:00
12	ORD-261105-KGUFSV	12	2	4	10	\N	\N	Website Company Profile Lengkap	basic	Basic	Lima halaman statis dengan desain responsif.	14	2	0	IDR	1500000	1000	150000	1350000	1500000	pending_payment	\N	\N	\N	\N	2026-10-31 11:00:00	2026-10-31 12:30:00
13	ORD-261105-ZTYSP4	13	1	2	4	\N	\N	Map Roleplay Kota untuk Roblox	basic	Basic	Satu distrik kota dengan 6 bangunan bisa dimasuki.	10	1	0	IDR	400000	1000	40000	360000	400000	cancelled	\N	\N	\N	2026-10-29 16:50:00	2026-10-29 16:20:00	2026-10-29 17:50:00
14	ORD-261105-RARTTR	12	1	\N	\N	1	1	Map Roblox untuk event ulang tahun sekolah	\N	\N	Butuh map dengan panggung, area lomba lari, dan spawn untuk 40 pemain. Tema warna sekolah biru putih.\n\nPenawaran freelancer:\nSaya sudah membuat beberapa map event sekolah. Termasuk panggung, area lomba, dan uji main bersama tim Anda.	12	2	0	IDR	1200000	1000	120000	1080000	1200000	in_progress	2026-11-04 08:20:00	\N	\N	\N	2026-11-04 08:10:00	2026-11-04 09:40:00
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: payment_events; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.payment_events (id, payment_id, event, from_status, to_status, user_id, payload, ip_address, created_at) FROM stdin;
12	7	created	\N	pending	11	{"amount": 750000, "method": "qris"}	\N	2026-10-20 10:25:00
13	7	simulated_success	pending	success	11	{"amount": 750000}	\N	2026-10-20 10:25:00
14	8	created	\N	pending	12	{"amount": 650000, "method": "virtual_account"}	\N	2026-10-26 14:15:00
15	8	simulated_success	pending	success	12	{"amount": 650000}	\N	2026-10-26 14:15:00
16	9	created	\N	pending	13	{"amount": 75000, "method": "ewallet"}	\N	2026-11-02 09:40:00
17	9	simulated_success	pending	success	13	{"amount": 75000}	\N	2026-11-02 09:40:00
18	10	created	\N	pending	11	{"amount": 120000, "method": "qris"}	\N	2026-11-03 19:55:00
19	11	created	\N	pending	12	{"amount": 1500000, "method": "virtual_account"}	\N	2026-10-31 11:10:00
20	11	simulated_failed	pending	failed	12	\N	\N	2026-10-31 11:10:00
21	12	created	\N	pending	13	{"amount": 400000, "method": "qris"}	\N	2026-10-29 16:30:00
22	12	cancelled_with_order	pending	cancelled	13	\N	\N	2026-10-29 16:30:00
23	13	created	\N	pending	12	{"amount": 1200000, "method": "qris"}	\N	2026-11-04 08:20:00
24	13	simulated_success	pending	success	12	{"amount": 1200000}	\N	2026-11-04 08:20:00
\.


--
-- Data for Name: payments; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.payments (id, transaction_code, order_id, customer_id, method, provider, provider_reference, currency, amount, commission_amount, freelancer_amount, status, expires_at, paid_at, failed_at, created_at, updated_at) FROM stdin;
7	TRX-20261105-9ITVONE9	8	11	qris	dummy	DEMO-QRIS-TRX-20261105-9ITVONE9	IDR	750000	75000	675000	success	2026-10-21 10:20:00	2026-10-20 10:25:00	\N	2026-10-20 10:20:00	2026-10-20 10:25:00
8	TRX-20261105-3HUSNLEB	9	12	virtual_account	dummy	8808673062273742	IDR	650000	65000	585000	success	2026-10-27 14:10:00	2026-10-26 14:15:00	\N	2026-10-26 14:10:00	2026-10-26 14:15:00
9	TRX-20261105-ZB2EIYTW	10	13	ewallet	dummy	DEMO-EW-1052541677	IDR	75000	7500	67500	success	2026-11-03 09:35:00	2026-11-02 09:40:00	\N	2026-11-02 09:35:00	2026-11-02 09:40:00
10	TRX-20261105-NYVFG1AW	11	11	qris	dummy	DEMO-QRIS-TRX-20261105-NYVFG1AW	IDR	120000	12000	108000	pending	2026-11-06 12:25:02	\N	\N	2026-11-03 19:50:00	2026-11-03 19:55:00
11	TRX-20261105-WELSAH7Y	12	12	virtual_account	dummy	8808001811185571	IDR	1500000	150000	1350000	failed	2026-11-01 11:05:00	\N	2026-10-31 11:10:00	2026-10-31 11:05:00	2026-10-31 11:10:00
12	TRX-20261105-HMHMVFXT	13	13	qris	dummy	DEMO-QRIS-TRX-20261105-HMHMVFXT	IDR	400000	40000	360000	cancelled	2026-10-30 16:25:00	\N	\N	2026-10-29 16:25:00	2026-10-29 16:30:00
13	TRX-20261105-WEAYPDLT	14	12	qris	dummy	DEMO-QRIS-TRX-20261105-WEAYPDLT	IDR	1200000	120000	1080000	success	2026-11-05 08:15:00	2026-11-04 08:20:00	\N	2026-11-04 08:15:00	2026-11-04 08:20:00
\.


--
-- Data for Name: portfolio_items; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.portfolio_items (id, user_id, category_id, title, description, image_path, project_url, created_at, updated_at, is_visible) FROM stdin;
1	1	1	Obby Candy Land 40 Stage	Map obby bertema permen untuk komunitas Roblox sekolah.	portfolio/1/demo-obby-candy-land-40-stage.png	\N	2026-11-05 11:09:21	2026-11-05 11:09:21	t
2	1	1	Tycoon Pabrik Roti	Game tycoon dengan sistem upgrade dan leaderboard.	portfolio/1/demo-tycoon-pabrik-roti.png	\N	2026-11-05 11:09:21	2026-11-05 11:09:21	t
3	2	2	Website Toko Kue Rumahan	Landing page dengan katalog produk dan pemesanan via WhatsApp.	portfolio/2/demo-website-toko-kue-rumahan.png	\N	2026-11-05 11:09:22	2026-11-05 11:09:22	t
4	2	2	Portal Himpunan Mahasiswa	Website organisasi dengan berita dan agenda kegiatan.	portfolio/2/demo-portal-himpunan-mahasiswa.png	\N	2026-11-05 11:09:22	2026-11-05 11:09:22	t
5	3	3	Poster Festival Musik Kampus	Poster utama dan turunan media sosial untuk festival musik.	portfolio/3/demo-poster-festival-musik-kampus.png	\N	2026-11-05 11:09:22	2026-11-05 11:09:22	t
6	3	3	Identitas Visual Kedai Kopi	Logo, menu, dan feed Instagram untuk kedai kopi lokal.	portfolio/3/demo-identitas-visual-kedai-kopi.png	\N	2026-11-05 11:09:22	2026-11-05 11:09:22	t
\.


--
-- Data for Name: projects; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.projects (id, customer_id, category_id, title, description, budget_min, budget_max, deadline, status, closed_at, created_at, updated_at) FROM stdin;
1	12	1	Map Roblox untuk event ulang tahun sekolah	Butuh map dengan panggung, area lomba lari, dan spawn untuk 40 pemain. Tema warna sekolah biru putih.	500000	1500000	2026-12-03	in_progress	\N	2026-11-05 12:25:02	2026-11-05 12:25:02
2	13	2	Website profil komunitas fotografi kampus	Website sederhana berisi profil, galeri foto, dan jadwal kegiatan. Bisa diperbarui oleh pengurus.	\N	2000000	2026-12-03	open	\N	2026-11-05 12:25:02	2026-11-05 12:25:02
\.


--
-- Data for Name: proposals; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.proposals (id, project_id, freelancer_id, price, delivery_days, revisions, message, status, created_at, updated_at) FROM stdin;
1	1	1	1200000	12	2	Saya sudah membuat beberapa map event sekolah. Termasuk panggung, area lomba, dan uji main bersama tim Anda.	accepted	2026-11-05 12:25:02	2026-11-05 12:25:02
2	1	3	900000	10	2	Saya bisa bantu dari sisi desain visual dan dekorasi map agar tampil meriah.	rejected	2026-11-05 12:25:02	2026-11-05 12:25:02
3	2	2	1750000	14	2	Saya akan membuat website responsif dengan panel admin sederhana untuk galeri dan jadwal.	pending	2026-11-05 12:25:02	2026-11-05 12:25:02
\.


--
-- Data for Name: reviews; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.reviews (id, order_id, service_id, customer_id, freelancer_id, rating, comment, is_visible, created_at, updated_at) FROM stdin;
1	8	1	11	1	5	Mapnya keren dan rapi, anak-anak suka sekali!	t	2026-10-20 11:55:00	2026-10-20 11:55:00
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.roles (id, name, label, created_at, updated_at) FROM stdin;
1	customer	Customer	2026-11-05 11:00:48	2026-11-05 11:00:48
2	freelancer	Freelancer	2026-11-05 11:00:48	2026-11-05 11:00:48
3	admin	Admin	2026-11-05 11:00:48	2026-11-05 11:00:48
\.


--
-- Data for Name: service_packages; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.service_packages (id, service_id, tier, name, description, price, delivery_days, revisions, is_active, created_at, updated_at) FROM stdin;
1	1	basic	Basic	10 stage obby dengan satu tema dan checkpoint.	150000	7	1	t	2026-11-05 11:01:12	2026-11-05 11:01:12
2	1	standard	Standard	25 stage, leaderboard waktu, dan efek partikel.	350000	10	2	t	2026-11-05 11:01:12	2026-11-05 11:01:12
3	1	premium	Premium	50 stage, sistem gamepass skip stage, dan musik latar.	750000	14	4	t	2026-11-05 11:01:12	2026-11-05 11:01:12
4	2	basic	Basic	Satu distrik kota dengan 6 bangunan bisa dimasuki.	400000	10	1	t	2026-11-05 11:01:12	2026-11-05 11:01:12
5	2	standard	Standard	Dua distrik, sistem pekerjaan sederhana, dan kendaraan.	900000	14	2	t	2026-11-05 11:01:12	2026-11-05 11:01:12
6	2	premium	Premium	Kota lengkap, sistem uang, rumah bisa dibeli, dan admin panel.	1800000	21	3	t	2026-11-05 11:01:12	2026-11-05 11:01:12
7	3	basic	Basic	Satu halaman dengan 4 section dan tombol WhatsApp.	300000	5	1	t	2026-11-05 11:01:13	2026-11-05 11:01:13
8	3	standard	Standard	Hingga 7 section, formulir kontak, dan optimasi SEO dasar.	650000	7	2	t	2026-11-05 11:01:13	2026-11-05 11:01:13
9	3	premium	Premium	Desain custom, animasi ringan, dan panduan update konten.	1200000	10	3	t	2026-11-05 11:01:13	2026-11-05 11:01:13
10	4	basic	Basic	Lima halaman statis dengan desain responsif.	1500000	14	2	t	2026-11-05 11:01:13	2026-11-05 11:01:13
11	4	standard	Standard	Hingga 10 halaman dan panel admin untuk berita.	3000000	21	3	t	2026-11-05 11:01:13	2026-11-05 11:01:13
12	4	premium	Premium	Panel admin lengkap, multi bahasa, dan pelatihan penggunaan.	5500000	30	5	t	2026-11-05 11:01:13	2026-11-05 11:01:13
13	5	basic	Basic	Satu desain poster ukuran A4 atau feed Instagram.	75000	3	1	t	2026-11-05 11:01:13	2026-11-05 11:01:13
14	5	standard	Standard	Poster A3 + versi story Instagram.	150000	3	3	t	2026-11-05 11:01:13	2026-11-05 11:01:13
15	5	premium	Premium	Poster, banner, dan tiga turunan media sosial.	300000	5	5	t	2026-11-05 11:01:13	2026-11-05 11:01:13
16	6	basic	Basic	Tiga desain feed dengan template yang bisa dipakai ulang.	120000	4	1	t	2026-11-05 11:01:13	2026-11-05 11:01:13
17	6	standard	Standard	Sembilan desain feed membentuk grid yang serasi.	250000	6	2	t	2026-11-05 11:01:13	2026-11-05 11:01:13
18	6	premium	Premium	Delapan belas desain feed + lima desain story.	500000	10	3	t	2026-11-05 11:01:13	2026-11-05 11:01:13
19	7	basic	Basic	Dua konsep logo.	200000	5	2	t	2026-11-05 11:01:13	2026-11-05 11:01:13
\.


--
-- Data for Name: services; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.services (id, user_id, category_id, title, slug, description, thumbnail_path, status, published_at, created_at, updated_at, rejection_reason, moderated_by, moderated_at) FROM stdin;
1	1	1	Desain Map Obby Roblox Custom	desain-map-obby-roblox-custom	Map obby dengan tema sesuai keinginanmu: hutan, luar angkasa, candy land, dan lainnya.\nSudah termasuk checkpoint, kill brick, dan uji main sebelum diserahkan.	\N	published	2026-11-03 11:01:12	2026-11-05 11:01:12	2026-11-05 11:01:12	\N	\N	\N
2	1	1	Map Roleplay Kota untuk Roblox	map-roleplay-kota-untuk-roblox	Kota roleplay lengkap dengan jalan, rumah, toko, dan sekolah.\nCocok untuk komunitas atau grup Roblox yang ingin punya game sendiri.	\N	published	2026-10-30 11:01:12	2026-11-05 11:01:12	2026-11-05 11:01:12	\N	\N	\N
3	2	2	Landing Page Responsif untuk UMKM	landing-page-responsif-untuk-umkm	Landing page satu halaman yang cepat, responsif, dan siap dipakai untuk promosi.\nTermasuk tombol WhatsApp dan formulir kontak.	\N	published	2026-11-04 11:01:13	2026-11-05 11:01:13	2026-11-05 11:01:13	\N	\N	\N
4	2	2	Website Company Profile Lengkap	website-company-profile-lengkap	Website multi-halaman untuk perusahaan atau organisasi, lengkap dengan halaman layanan, portofolio, dan kontak.	\N	published	2026-10-27 11:01:13	2026-11-05 11:01:13	2026-11-05 11:01:13	\N	\N	\N
5	3	3	Desain Poster Event & Promosi	desain-poster-event-promosi	Poster menarik untuk seminar, lomba, konser, atau promo produk.\nFile siap cetak (PDF) dan versi media sosial (PNG).	\N	published	2026-11-02 11:01:13	2026-11-05 11:01:13	2026-11-05 11:01:13	\N	\N	\N
6	3	3	Paket Desain Feed Instagram	paket-desain-feed-instagram	Desain konten feed Instagram yang konsisten dengan identitas brand kamu.	\N	published	2026-10-24 11:01:13	2026-11-05 11:01:13	2026-11-05 11:01:13	\N	\N	\N
7	3	3	Desain Logo Minimalis	desain-logo-minimalis	Contoh layanan berstatus draf: tidak tampil di katalog publik.	\N	draft	\N	2026-11-05 11:01:13	2026-11-05 11:30:50	\N	\N	\N
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
\.


--
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.settings (key, value, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: user_roles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.user_roles (user_id, role_id, created_at, updated_at) FROM stdin;
1	1	2026-11-05 11:01:12	2026-11-05 11:01:12
1	2	2026-11-05 11:01:12	2026-11-05 11:01:12
2	1	2026-11-05 11:01:13	2026-11-05 11:01:13
2	2	2026-11-05 11:01:13	2026-11-05 11:01:13
3	1	2026-11-05 11:01:13	2026-11-05 11:01:13
3	2	2026-11-05 11:01:13	2026-11-05 11:01:13
10	3	2026-11-05 12:25:01	2026-11-05 12:25:01
11	1	2026-11-05 12:25:01	2026-11-05 12:25:01
12	1	2026-11-05 12:25:02	2026-11-05 12:25:02
13	1	2026-11-05 12:25:02	2026-11-05 12:25:02
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, is_active, avatar_path) FROM stdin;
11	Budi Santoso	budi@demo.creativehub.test	2026-11-05 12:25:01	$2y$12$oq7A2AP8S12R1mwvSuztfOuMJ1FjGq7AZVKDpeNnnSxXhXitbZkKC	\N	2026-11-05 12:25:01	2026-11-05 12:25:01	t	\N
12	Siti Rahmawati	siti@demo.creativehub.test	2026-11-05 12:25:02	$2y$12$oq7A2AP8S12R1mwvSuztfOuMJ1FjGq7AZVKDpeNnnSxXhXitbZkKC	\N	2026-11-05 12:25:02	2026-11-05 12:25:02	t	\N
13	Andi Wijaya	andi@demo.creativehub.test	2026-11-05 12:25:02	$2y$12$oq7A2AP8S12R1mwvSuztfOuMJ1FjGq7AZVKDpeNnnSxXhXitbZkKC	\N	2026-11-05 12:25:02	2026-11-05 12:25:02	t	\N
10	Admin Demo CreativeHub	admin@demo.creativehub.test	2026-11-05 12:25:01	$2y$12$oq7A2AP8S12R1mwvSuztfOuMJ1FjGq7AZVKDpeNnnSxXhXitbZkKC	\N	2026-11-05 12:25:01	2026-11-05 12:25:01	t	\N
3	Abdul Rachman	user3@contoh.creativehub.test	2026-11-05 11:01:13	$2y$12$oq7A2AP8S12R1mwvSuztfOuMJ1FjGq7AZVKDpeNnnSxXhXitbZkKC	\N	2026-11-05 11:01:13	2026-11-05 14:29:42	t	\N
2	Nuzululhaz Khofifah	user2@contoh.creativehub.test	\N	$2y$12$oq7A2AP8S12R1mwvSuztfOuMJ1FjGq7AZVKDpeNnnSxXhXitbZkKC	\N	2026-11-05 11:01:13	2026-11-05 14:30:04	t	\N
1	Robby Fatkhurozi	user1@contoh.creativehub.test	2026-11-05 11:01:12	$2y$12$oq7A2AP8S12R1mwvSuztfOuMJ1FjGq7AZVKDpeNnnSxXhXitbZkKC	\N	2026-11-05 11:01:12	2026-11-05 14:30:31	t	\N
\.


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 7, true);


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categories_id_seq', 3, true);


--
-- Name: conversations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.conversations_id_seq', 3, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: freelancer_profiles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.freelancer_profiles_id_seq', 5, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.messages_id_seq', 2, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 20, true);


--
-- Name: order_deliveries_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.order_deliveries_id_seq', 2, true);


--
-- Name: order_revisions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.order_revisions_id_seq', 1, false);


--
-- Name: order_status_histories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.order_status_histories_id_seq', 35, true);


--
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.orders_id_seq', 15, true);


--
-- Name: payment_events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.payment_events_id_seq', 24, true);


--
-- Name: payments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.payments_id_seq', 13, true);


--
-- Name: portfolio_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.portfolio_items_id_seq', 6, true);


--
-- Name: projects_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.projects_id_seq', 2, true);


--
-- Name: proposals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.proposals_id_seq', 3, true);


--
-- Name: reviews_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.reviews_id_seq', 1, true);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.roles_id_seq', 3, true);


--
-- Name: service_packages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.service_packages_id_seq', 20, true);


--
-- Name: services_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.services_id_seq', 8, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 19, true);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_slug_unique UNIQUE (slug);


--
-- Name: conversations conversations_order_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_order_id_unique UNIQUE (order_id);


--
-- Name: conversations conversations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_pkey PRIMARY KEY (id);


--
-- Name: conversations conversations_proposal_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_proposal_id_unique UNIQUE (proposal_id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: freelancer_profiles freelancer_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freelancer_profiles
    ADD CONSTRAINT freelancer_profiles_pkey PRIMARY KEY (id);


--
-- Name: freelancer_profiles freelancer_profiles_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freelancer_profiles
    ADD CONSTRAINT freelancer_profiles_user_id_unique UNIQUE (user_id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: order_deliveries order_deliveries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_deliveries
    ADD CONSTRAINT order_deliveries_pkey PRIMARY KEY (id);


--
-- Name: order_revisions order_revisions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_revisions
    ADD CONSTRAINT order_revisions_pkey PRIMARY KEY (id);


--
-- Name: order_status_histories order_status_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_pkey PRIMARY KEY (id);


--
-- Name: orders orders_order_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_order_number_unique UNIQUE (order_number);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: orders orders_proposal_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_proposal_id_unique UNIQUE (proposal_id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payment_events payment_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_events
    ADD CONSTRAINT payment_events_pkey PRIMARY KEY (id);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: payments payments_transaction_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_transaction_code_unique UNIQUE (transaction_code);


--
-- Name: portfolio_items portfolio_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portfolio_items
    ADD CONSTRAINT portfolio_items_pkey PRIMARY KEY (id);


--
-- Name: projects projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);


--
-- Name: proposals proposals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_pkey PRIMARY KEY (id);


--
-- Name: reviews reviews_order_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_order_id_unique UNIQUE (order_id);


--
-- Name: reviews reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_pkey PRIMARY KEY (id);


--
-- Name: roles roles_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_unique UNIQUE (name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: service_packages service_packages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_packages
    ADD CONSTRAINT service_packages_pkey PRIMARY KEY (id);


--
-- Name: service_packages service_packages_service_id_tier_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_packages
    ADD CONSTRAINT service_packages_service_id_tier_unique UNIQUE (service_id, tier);


--
-- Name: services services_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_pkey PRIMARY KEY (id);


--
-- Name: services services_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_slug_unique UNIQUE (slug);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (key);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (user_id, role_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: audit_logs_action_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_action_created_at_index ON public.audit_logs USING btree (action, created_at);


--
-- Name: audit_logs_auditable_type_auditable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_auditable_type_auditable_id_index ON public.audit_logs USING btree (auditable_type, auditable_id);


--
-- Name: audit_logs_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_created_at_index ON public.audit_logs USING btree (created_at);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: categories_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_is_active_sort_order_index ON public.categories USING btree (is_active, sort_order);


--
-- Name: conversations_customer_id_last_message_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_customer_id_last_message_at_index ON public.conversations USING btree (customer_id, last_message_at);


--
-- Name: conversations_freelancer_id_last_message_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversations_freelancer_id_last_message_at_index ON public.conversations USING btree (freelancer_id, last_message_at);


--
-- Name: freelancer_profiles_availability_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX freelancer_profiles_availability_index ON public.freelancer_profiles USING btree (availability);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: messages_conversation_id_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX messages_conversation_id_id_index ON public.messages USING btree (conversation_id, id);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: order_deliveries_order_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_deliveries_order_id_created_at_index ON public.order_deliveries USING btree (order_id, created_at);


--
-- Name: order_revisions_order_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_revisions_order_id_created_at_index ON public.order_revisions USING btree (order_id, created_at);


--
-- Name: order_status_histories_order_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX order_status_histories_order_id_created_at_index ON public.order_status_histories USING btree (order_id, created_at);


--
-- Name: orders_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_created_at_index ON public.orders USING btree (created_at);


--
-- Name: orders_customer_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_customer_id_status_index ON public.orders USING btree (customer_id, status);


--
-- Name: orders_freelancer_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_freelancer_id_status_index ON public.orders USING btree (freelancer_id, status);


--
-- Name: payment_events_payment_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payment_events_payment_id_created_at_index ON public.payment_events USING btree (payment_id, created_at);


--
-- Name: payments_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_created_at_index ON public.payments USING btree (created_at);


--
-- Name: payments_customer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_customer_id_index ON public.payments USING btree (customer_id);


--
-- Name: payments_one_pending_per_order; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX payments_one_pending_per_order ON public.payments USING btree (order_id) WHERE ((status)::text = 'pending'::text);


--
-- Name: payments_one_success_per_order; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX payments_one_success_per_order ON public.payments USING btree (order_id) WHERE ((status)::text = 'success'::text);


--
-- Name: payments_status_paid_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_status_paid_at_index ON public.payments USING btree (status, paid_at);


--
-- Name: portfolio_items_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX portfolio_items_user_id_created_at_index ON public.portfolio_items USING btree (user_id, created_at);


--
-- Name: projects_customer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_customer_id_index ON public.projects USING btree (customer_id);


--
-- Name: projects_status_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX projects_status_category_id_index ON public.projects USING btree (status, category_id);


--
-- Name: proposals_freelancer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX proposals_freelancer_id_index ON public.proposals USING btree (freelancer_id);


--
-- Name: proposals_one_accepted_per_project; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX proposals_one_accepted_per_project ON public.proposals USING btree (project_id) WHERE ((status)::text = 'accepted'::text);


--
-- Name: proposals_one_active_per_freelancer; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX proposals_one_active_per_freelancer ON public.proposals USING btree (project_id, freelancer_id) WHERE ((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('accepted'::character varying)::text]));


--
-- Name: proposals_project_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX proposals_project_id_status_index ON public.proposals USING btree (project_id, status);


--
-- Name: reviews_freelancer_id_is_visible_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_freelancer_id_is_visible_index ON public.reviews USING btree (freelancer_id, is_visible);


--
-- Name: reviews_service_id_is_visible_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX reviews_service_id_is_visible_index ON public.reviews USING btree (service_id, is_visible);


--
-- Name: service_packages_service_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX service_packages_service_id_is_active_index ON public.service_packages USING btree (service_id, is_active);


--
-- Name: services_status_category_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX services_status_category_id_index ON public.services USING btree (status, category_id);


--
-- Name: services_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX services_user_id_index ON public.services USING btree (user_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: user_roles_role_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_roles_role_id_index ON public.user_roles USING btree (role_id);


--
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: conversations conversations_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: conversations conversations_freelancer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_freelancer_id_foreign FOREIGN KEY (freelancer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: conversations conversations_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: conversations conversations_proposal_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_proposal_id_foreign FOREIGN KEY (proposal_id) REFERENCES public.proposals(id) ON DELETE CASCADE;


--
-- Name: freelancer_profiles freelancer_profiles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freelancer_profiles
    ADD CONSTRAINT freelancer_profiles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: messages messages_conversation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_conversation_id_foreign FOREIGN KEY (conversation_id) REFERENCES public.conversations(id) ON DELETE CASCADE;


--
-- Name: messages messages_sender_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_sender_id_foreign FOREIGN KEY (sender_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: order_deliveries order_deliveries_freelancer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_deliveries
    ADD CONSTRAINT order_deliveries_freelancer_id_foreign FOREIGN KEY (freelancer_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: order_deliveries order_deliveries_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_deliveries
    ADD CONSTRAINT order_deliveries_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_revisions order_revisions_order_delivery_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_revisions
    ADD CONSTRAINT order_revisions_order_delivery_id_foreign FOREIGN KEY (order_delivery_id) REFERENCES public.order_deliveries(id) ON DELETE SET NULL;


--
-- Name: order_revisions order_revisions_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_revisions
    ADD CONSTRAINT order_revisions_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: order_revisions order_revisions_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_revisions
    ADD CONSTRAINT order_revisions_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: order_status_histories order_status_histories_changed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_changed_by_foreign FOREIGN KEY (changed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: order_status_histories order_status_histories_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.order_status_histories
    ADD CONSTRAINT order_status_histories_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: orders orders_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: orders orders_freelancer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_freelancer_id_foreign FOREIGN KEY (freelancer_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: orders orders_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE RESTRICT;


--
-- Name: orders orders_proposal_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_proposal_id_foreign FOREIGN KEY (proposal_id) REFERENCES public.proposals(id) ON DELETE RESTRICT;


--
-- Name: orders orders_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE RESTRICT;


--
-- Name: orders orders_service_package_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_service_package_id_foreign FOREIGN KEY (service_package_id) REFERENCES public.service_packages(id) ON DELETE RESTRICT;


--
-- Name: payment_events payment_events_payment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_events
    ADD CONSTRAINT payment_events_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES public.payments(id) ON DELETE CASCADE;


--
-- Name: payment_events payment_events_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_events
    ADD CONSTRAINT payment_events_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: payments payments_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: payments payments_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE RESTRICT;


--
-- Name: portfolio_items portfolio_items_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portfolio_items
    ADD CONSTRAINT portfolio_items_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE SET NULL;


--
-- Name: portfolio_items portfolio_items_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.portfolio_items
    ADD CONSTRAINT portfolio_items_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: projects projects_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE RESTRICT;


--
-- Name: projects projects_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: proposals proposals_freelancer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_freelancer_id_foreign FOREIGN KEY (freelancer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: proposals proposals_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.proposals
    ADD CONSTRAINT proposals_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: reviews reviews_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: reviews reviews_freelancer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_freelancer_id_foreign FOREIGN KEY (freelancer_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: reviews reviews_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE RESTRICT;


--
-- Name: reviews reviews_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE RESTRICT;


--
-- Name: service_packages service_packages_service_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.service_packages
    ADD CONSTRAINT service_packages_service_id_foreign FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE CASCADE;


--
-- Name: services services_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE RESTRICT;


--
-- Name: services services_moderated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_moderated_by_foreign FOREIGN KEY (moderated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: services services_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE RESTRICT;


--
-- Name: user_roles user_roles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict C2MeQhPEQEnsp6aMIdRvQ4P6T1v7LwFUcEf4WaGhklTZ2vscw7PzxG5zeWZNwbN

