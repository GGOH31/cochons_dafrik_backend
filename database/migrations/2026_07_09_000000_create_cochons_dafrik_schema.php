<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            CREATE EXTENSION IF NOT EXISTS "pgcrypto";

            -- 1. ÉNUMÉRATIONS
            CREATE TYPE user_role AS ENUM (\'client\', \'vendeur\', \'grossiste\', \'admin\');
            CREATE TYPE account_status AS ENUM (\'pending\', \'active\', \'suspended\', \'rejected\');
            CREATE TYPE order_type AS ENUM (\'b2c\', \'b2b\');
            CREATE TYPE order_status AS ENUM (
              \'pending_payment\',
              \'paid\',
              \'accepted\',
              \'preparing\',
              \'delivering\',
              \'delivered\',
              \'completed\',
              \'refused\',
              \'cancelled\',
              \'disputed\'
            );
            CREATE TYPE delivery_mode AS ENUM (\'delivery\', \'pickup\');
            CREATE TYPE payment_provider AS ENUM (\'orange_money\', \'mtn_momo\', \'moov_money\', \'wave\', \'card\');
            CREATE TYPE payment_status AS ENUM (\'initiated\', \'success\', \'failed\', \'refunded\');
            CREATE TYPE escrow_status AS ENUM (\'held\', \'released\', \'refunded\', \'frozen\');
            CREATE TYPE tx_type AS ENUM (\'escrow_hold\', \'escrow_release\', \'commission\', \'refund\', \'withdrawal\', \'adjustment\');
            CREATE TYPE withdrawal_status AS ENUM (\'pending\', \'processing\', \'done\', \'rejected\');
            CREATE TYPE notif_channel AS ENUM (\'sms\', \'push\');
            CREATE TYPE dispute_status AS ENUM (\'open\', \'resolved_refund\', \'resolved_release\');
            CREATE TYPE promo_type AS ENUM (\'percentage\', \'fixed_price\');

            -- 2. UTILISATEURS & BOUTIQUES
            CREATE TABLE users (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              role          user_role NOT NULL DEFAULT \'client\',
              phone         VARCHAR(20) NOT NULL UNIQUE,
              phone_verified_at TIMESTAMPTZ,
              full_name     VARCHAR(120) NOT NULL,
              email         VARCHAR(160) UNIQUE,
              password_hash TEXT,
              fcm_token     TEXT,
              status        account_status NOT NULL DEFAULT \'active\',
              created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
              updated_at    TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            CREATE TABLE restaurants (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              owner_id      UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
              name          VARCHAR(140) NOT NULL,
              description   TEXT,
              logo_url      TEXT,
              commune       VARCHAR(80),
              address       TEXT,
              latitude      NUMERIC(9,6),
              longitude     NUMERIC(9,6),
              is_open       BOOLEAN NOT NULL DEFAULT false,
              delivery_fee_fcfa   INTEGER NOT NULL DEFAULT 0 CHECK (delivery_fee_fcfa >= 0),
              min_order_fcfa      INTEGER NOT NULL DEFAULT 0,
              status        account_status NOT NULL DEFAULT \'pending\',
              validated_by  UUID REFERENCES users(id),
              validated_at  TIMESTAMPTZ,
              rating_avg    NUMERIC(2,1) NOT NULL DEFAULT 0,
              rating_count  INTEGER NOT NULL DEFAULT 0,
              created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
              updated_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
              UNIQUE (owner_id)
            );

            CREATE TABLE addresses (
              id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              user_id    UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
              label      VARCHAR(60) NOT NULL DEFAULT \'Maison\',
              commune    VARCHAR(80),
              details    TEXT NOT NULL,
              latitude   NUMERIC(9,6),
              longitude  NUMERIC(9,6),
              is_default BOOLEAN NOT NULL DEFAULT false,
              created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            -- 3. CATALOGUE & PROMOTIONS
            CREATE TABLE dishes (
              id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              restaurant_id      UUID NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
              name         VARCHAR(160) NOT NULL,
              description  TEXT,
              photo_url    TEXT,
              unit         VARCHAR(30) NOT NULL DEFAULT \'portion\',
              price_fcfa   INTEGER NOT NULL CHECK (price_fcfa > 0),
              prep_minutes INTEGER,
              is_active    BOOLEAN NOT NULL DEFAULT true,
              created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
              updated_at   TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE INDEX idx_dishes_shop ON dishes(restaurant_id) WHERE is_active;

            CREATE TABLE promotions (
              id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              restaurant_id     UUID NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
              dish_id  UUID REFERENCES dishes(id) ON DELETE CASCADE,
              title       VARCHAR(140) NOT NULL,
              promo_type  promo_type NOT NULL DEFAULT \'percentage\',
              value       INTEGER NOT NULL CHECK (value > 0),
              starts_at   TIMESTAMPTZ NOT NULL,
              ends_at     TIMESTAMPTZ NOT NULL,
              is_active   BOOLEAN NOT NULL DEFAULT true,
              created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
              CHECK (ends_at > starts_at)
            );
            CREATE INDEX idx_promotions_active ON promotions(restaurant_id, starts_at, ends_at) WHERE is_active;

            -- 4. COMMANDES
            CREATE TABLE orders (
              id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              reference      VARCHAR(20) NOT NULL UNIQUE,
              order_type     order_type NOT NULL,
              buyer_id       UUID NOT NULL REFERENCES users(id),
              restaurant_id        UUID NOT NULL REFERENCES restaurants(id),
              status         order_status NOT NULL DEFAULT \'pending_payment\',
              delivery_mode  delivery_mode NOT NULL DEFAULT \'delivery\',
              address_id     UUID REFERENCES addresses(id),
              delivery_code  CHAR(4),
              subtotal_fcfa  INTEGER NOT NULL CHECK (subtotal_fcfa >= 0),
              delivery_fcfa  INTEGER NOT NULL DEFAULT 0,
              total_fcfa     INTEGER NOT NULL CHECK (total_fcfa >= 0),
              commission_pct   NUMERIC(5,2) NOT NULL,
              commission_fcfa  INTEGER NOT NULL DEFAULT 0,
              seller_net_fcfa  INTEGER NOT NULL DEFAULT 0,
              accepted_at    TIMESTAMPTZ,
              delivered_at   TIMESTAMPTZ,
              confirmed_at   TIMESTAMPTZ,
              auto_confirm_at TIMESTAMPTZ,
              cancel_reason  TEXT,
              created_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
              updated_at     TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE INDEX idx_orders_buyer ON orders(buyer_id, created_at DESC);
            CREATE INDEX idx_orders_shop  ON orders(restaurant_id, status, created_at DESC);
            CREATE INDEX idx_orders_auto_confirm ON orders(auto_confirm_at) WHERE status = \'delivered\';

            CREATE TABLE order_items (
              id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              order_id     UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
              dish_id   UUID NOT NULL REFERENCES dishes(id),
              dish_name VARCHAR(160) NOT NULL,
              unit_price_fcfa INTEGER NOT NULL,
              quantity     NUMERIC(8,2) NOT NULL CHECK (quantity > 0),
              options      JSONB,
              line_total_fcfa INTEGER NOT NULL
            );

            CREATE TABLE order_status_history (
              id         BIGSERIAL PRIMARY KEY,
              order_id   UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
              status     order_status NOT NULL,
              changed_by UUID REFERENCES users(id),
              note       TEXT,
              created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            -- 5. PAIEMENTS, SÉQUESTRE, PORTEFEUILLES
            CREATE TABLE payments (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              order_id      UUID NOT NULL REFERENCES orders(id),
              provider      payment_provider NOT NULL,
              provider_ref  VARCHAR(120),
              amount_fcfa   INTEGER NOT NULL CHECK (amount_fcfa > 0),
              status        payment_status NOT NULL DEFAULT \'initiated\',
              payload       JSONB,
              paid_at       TIMESTAMPTZ,
              refunded_at   TIMESTAMPTZ,
              created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE UNIQUE INDEX idx_payments_provider_ref ON payments(provider, provider_ref) WHERE provider_ref IS NOT NULL;

            CREATE TABLE escrows (
              id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              order_id     UUID NOT NULL UNIQUE REFERENCES orders(id),
              payment_id   UUID NOT NULL REFERENCES payments(id),
              amount_fcfa  INTEGER NOT NULL,
              status       escrow_status NOT NULL DEFAULT \'held\',
              held_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
              released_at  TIMESTAMPTZ,
              refunded_at  TIMESTAMPTZ
            );

            CREATE TABLE wallets (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              restaurant_id       UUID NOT NULL UNIQUE REFERENCES restaurants(id),
              balance_fcfa  BIGINT NOT NULL DEFAULT 0 CHECK (balance_fcfa >= 0),
              updated_at    TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            CREATE TABLE wallet_transactions (
              id           BIGSERIAL PRIMARY KEY,
              wallet_id    UUID REFERENCES wallets(id),
              order_id     UUID REFERENCES orders(id),
              tx_type      tx_type NOT NULL,
              amount_fcfa  BIGINT NOT NULL,
              balance_after BIGINT,
              note         TEXT,
              created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE INDEX idx_wtx_wallet ON wallet_transactions(wallet_id, created_at DESC);

            CREATE TABLE withdrawals (
              id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              wallet_id    UUID NOT NULL REFERENCES wallets(id),
              amount_fcfa  INTEGER NOT NULL CHECK (amount_fcfa > 0),
              provider     payment_provider NOT NULL,
              dest_phone   VARCHAR(20) NOT NULL,
              status       withdrawal_status NOT NULL DEFAULT \'pending\',
              processed_by UUID REFERENCES users(id),
              processed_at TIMESTAMPTZ,
              created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            -- 6. LITIGES, AVIS, NOTIFICATIONS
            CREATE TABLE disputes (
              id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              order_id     UUID NOT NULL UNIQUE REFERENCES orders(id),
              opened_by    UUID NOT NULL REFERENCES users(id),
              reason       TEXT NOT NULL,
              status       dispute_status NOT NULL DEFAULT \'open\',
              resolved_by  UUID REFERENCES users(id),
              resolution   TEXT,
              created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
              resolved_at  TIMESTAMPTZ
            );

            CREATE TABLE reviews (
              id         UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              order_id   UUID NOT NULL UNIQUE REFERENCES orders(id),
              restaurant_id    UUID NOT NULL REFERENCES restaurants(id),
              author_id  UUID NOT NULL REFERENCES users(id),
              rating     SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
              comment    TEXT,
              created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            CREATE TABLE notifications (
              id         BIGSERIAL PRIMARY KEY,
              user_id    UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
              order_id   UUID REFERENCES orders(id),
              channel    notif_channel NOT NULL,
              title      VARCHAR(160),
              body       TEXT NOT NULL,
              sent_at    TIMESTAMPTZ,
              error      TEXT,
              created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            CREATE TABLE otp_codes (
              id         BIGSERIAL PRIMARY KEY,
              phone      VARCHAR(20) NOT NULL,
              code_hash  TEXT NOT NULL,
              expires_at TIMESTAMPTZ NOT NULL,
              used_at    TIMESTAMPTZ,
              created_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );
            CREATE INDEX idx_otp_phone ON otp_codes(phone, expires_at DESC);

            -- 7. PARAMÈTRES PLATEFORME
            CREATE TABLE platform_settings (
              key        VARCHAR(60) PRIMARY KEY,
              value      JSONB NOT NULL,
              updated_by UUID REFERENCES users(id),
              updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            INSERT INTO platform_settings (key, value) VALUES
              (\'commission_rate_pct\',        \'3.0\'),
              (\'commission_rate_pct_b2c\',    \'null\'),
              (\'commission_rate_pct_b2b\',    \'null\'),
              (\'auto_confirm_delay_hours\',   \'24\'),
              (\'min_withdrawal_fcfa\',        \'5000\');

            CREATE TABLE restaurant_commission_overrides (
              restaurant_id    UUID PRIMARY KEY REFERENCES restaurants(id) ON DELETE CASCADE,
              rate_pct   NUMERIC(5,2) NOT NULL CHECK (rate_pct >= 0 AND rate_pct <= 100),
              updated_by UUID REFERENCES users(id),
              updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
            );

            -- resolve_commission_pct function
            CREATE OR REPLACE FUNCTION resolve_commission_pct(p_shop UUID, p_type order_type)
            RETURNS NUMERIC LANGUAGE sql STABLE AS $$
              SELECT COALESCE(
                (SELECT rate_pct FROM restaurant_commission_overrides WHERE restaurant_id = p_shop),
                NULLIF((SELECT value::text FROM platform_settings
                        WHERE key = \'commission_rate_pct_\' || p_type::text), \'null\')::numeric,
                (SELECT value::text::numeric FROM platform_settings
                 WHERE key = \'commission_rate_pct\'),
                3.0
              );
            $$;

            -- 8.1 Figer commission et net vendeur à la création de la commande
            CREATE OR REPLACE FUNCTION trg_orders_set_commission()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$
            BEGIN
              IF NEW.commission_pct IS NULL OR NEW.commission_pct = 0 THEN
                NEW.commission_pct := resolve_commission_pct(NEW.restaurant_id, NEW.order_type);
              END IF;
              NEW.commission_fcfa := round(NEW.total_fcfa * NEW.commission_pct / 100.0);
              NEW.seller_net_fcfa := NEW.total_fcfa - NEW.commission_fcfa;
              RETURN NEW;
            END $$;

            CREATE TRIGGER orders_set_commission
              BEFORE INSERT ON orders
              FOR EACH ROW EXECUTE FUNCTION trg_orders_set_commission();

            -- 8.2 Historiser chaque changement de statut (AFTER trigger)
            CREATE OR REPLACE FUNCTION trg_orders_history()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$
            BEGIN
              IF TG_OP = \'INSERT\' OR NEW.status IS DISTINCT FROM OLD.status THEN
                INSERT INTO order_status_history(order_id, status) VALUES (NEW.id, NEW.status);
              END IF;
              RETURN NEW;
            END $$;

            CREATE TRIGGER orders_history
              AFTER INSERT OR UPDATE ON orders
              FOR EACH ROW EXECUTE FUNCTION trg_orders_history();

            -- 8.2.1 Mettre à jour updated_at (BEFORE trigger)
            CREATE OR REPLACE FUNCTION trg_orders_update_timestamp()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$
            BEGIN
              NEW.updated_at := now();
              RETURN NEW;
            END $$;

            CREATE TRIGGER orders_update_timestamp
              BEFORE INSERT OR UPDATE ON orders
              FOR EACH ROW EXECUTE FUNCTION trg_orders_update_timestamp();

            -- 8.3 Recalcul de la note moyenne d\'une boutique
            CREATE OR REPLACE FUNCTION trg_reviews_rating()
            RETURNS TRIGGER LANGUAGE plpgsql AS $$
            BEGIN
              UPDATE restaurants s SET
                rating_avg   = (SELECT round(avg(rating)::numeric, 1) FROM reviews WHERE restaurant_id = NEW.restaurant_id),
                rating_count = (SELECT count(*) FROM reviews WHERE restaurant_id = NEW.restaurant_id)
              WHERE s.id = NEW.restaurant_id;
              RETURN NEW;
            END $$;

            CREATE TRIGGER reviews_rating
              AFTER INSERT ON reviews
              FOR EACH ROW EXECUTE FUNCTION trg_reviews_rating();

            -- 9. PROCÉDURE DE LIBÉRATION DU SÉQUESTRE
            CREATE OR REPLACE FUNCTION release_escrow(p_order UUID, p_confirmed_by UUID)
            RETURNS VOID LANGUAGE plpgsql AS $$
            DECLARE
              v_order   orders%ROWTYPE;
              v_escrow  escrows%ROWTYPE;
              v_wallet  wallets%ROWTYPE;
            BEGIN
              SELECT * INTO v_order FROM orders WHERE id = p_order FOR UPDATE;
              IF v_order.status NOT IN (\'delivered\') THEN
                RAISE EXCEPTION \'Commande % non libérable (statut %)\', v_order.reference, v_order.status;
              END IF;

              SELECT * INTO v_escrow FROM escrows WHERE order_id = p_order FOR UPDATE;
              IF v_escrow.status <> \'held\' THEN
                RAISE EXCEPTION \'Séquestre déjà traité (%).\', v_escrow.status;
              END IF;

              -- Portefeuille du vendeur/grossiste (créé si absent)
              INSERT INTO wallets (restaurant_id) VALUES (v_order.restaurant_id)
                ON CONFLICT (restaurant_id) DO NOTHING;
              SELECT * INTO v_wallet FROM wallets WHERE restaurant_id = v_order.restaurant_id FOR UPDATE;

              -- Crédit vendeur : total − commission
              UPDATE wallets SET balance_fcfa = balance_fcfa + v_order.seller_net_fcfa,
                                 updated_at = now()
                WHERE id = v_wallet.id;

              INSERT INTO wallet_transactions (wallet_id, order_id, tx_type, amount_fcfa, balance_after, note)
              VALUES (v_wallet.id, p_order, \'escrow_release\', v_order.seller_net_fcfa,
                      v_wallet.balance_fcfa + v_order.seller_net_fcfa,
                      \'Libération séquestre \' || v_order.reference);

              -- Commission plateforme (wallet_id NULL = compte Cochons d\'Afrik)
              INSERT INTO wallet_transactions (wallet_id, order_id, tx_type, amount_fcfa, note)
              VALUES (NULL, p_order, \'commission\', v_order.commission_fcfa,
                      \'Commission \' || v_order.commission_pct || \'% sur \' || v_order.reference);

              UPDATE escrows SET status = \'released\', released_at = now() WHERE id = v_escrow.id;
              UPDATE orders  SET status = \'completed\', confirmed_at = now() WHERE id = p_order;
            END $$;

            -- 10. DONNÉES DE DÉPART
            -- Vues
            CREATE VIEW v_admin_dashboard AS
            SELECT
              count(*) FILTER (WHERE status = \'completed\')                     AS commandes_terminees,
              count(*) FILTER (WHERE status IN (\'paid\',\'accepted\',\'preparing\',\'delivering\',\'delivered\')) AS commandes_en_cours,
              count(*) FILTER (WHERE status = \'disputed\')                      AS litiges_ouverts,
              COALESCE(sum(total_fcfa)      FILTER (WHERE status = \'completed\'), 0) AS volume_fcfa,
              COALESCE(sum(commission_fcfa) FILTER (WHERE status = \'completed\'), 0) AS commissions_fcfa
            FROM orders;

            CREATE VIEW v_escrow_en_cours AS
            SELECT o.reference, o.order_type, s.name AS boutique, e.amount_fcfa,
                   e.held_at, o.status, o.auto_confirm_at
            FROM escrows e
            JOIN orders o ON o.id = e.order_id
            JOIN restaurants  s ON s.id = o.restaurant_id
            WHERE e.status = \'held\'
            ORDER BY e.held_at;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('
            DROP VIEW IF EXISTS v_escrow_en_cours;
            DROP VIEW IF EXISTS v_admin_dashboard;
            DROP TABLE IF EXISTS restaurant_commission_overrides CASCADE;
            DROP TABLE IF EXISTS platform_settings CASCADE;
            DROP TABLE IF EXISTS otp_codes CASCADE;
            DROP TABLE IF EXISTS notifications CASCADE;
            DROP TABLE IF EXISTS reviews CASCADE;
            DROP TABLE IF EXISTS disputes CASCADE;
            DROP TABLE IF EXISTS withdrawals CASCADE;
            DROP TABLE IF EXISTS wallet_transactions CASCADE;
            DROP TABLE IF EXISTS wallets CASCADE;
            DROP TABLE IF EXISTS escrows CASCADE;
            DROP TABLE IF EXISTS payments CASCADE;
            DROP TABLE IF EXISTS order_status_history CASCADE;
            DROP TABLE IF EXISTS order_items CASCADE;
            DROP TABLE IF EXISTS orders CASCADE;
            DROP TABLE IF EXISTS promotions CASCADE;
            DROP TABLE IF EXISTS dishes CASCADE;
            DROP TABLE IF EXISTS addresses CASCADE;
            DROP TABLE IF EXISTS restaurants CASCADE;
            DROP TABLE IF EXISTS users CASCADE;

            DROP FUNCTION IF EXISTS release_escrow(UUID, UUID);
            DROP FUNCTION IF EXISTS trg_reviews_rating() CASCADE;
            DROP FUNCTION IF EXISTS trg_orders_history() CASCADE;
            DROP FUNCTION IF EXISTS trg_orders_update_timestamp() CASCADE;
            DROP FUNCTION IF EXISTS trg_orders_set_commission() CASCADE;
            DROP FUNCTION IF EXISTS resolve_commission_pct(UUID, order_type);

            DROP TYPE IF EXISTS promo_type;
            DROP TYPE IF EXISTS dispute_status;
            DROP TYPE IF EXISTS notif_channel;
            DROP TYPE IF EXISTS withdrawal_status;
            DROP TYPE IF EXISTS tx_type;
            DROP TYPE IF EXISTS escrow_status;
            DROP TYPE IF EXISTS payment_status;
            DROP TYPE IF EXISTS payment_provider;
            DROP TYPE IF EXISTS delivery_mode;
            DROP TYPE IF EXISTS order_status;
            DROP TYPE IF EXISTS order_type;
            DROP TYPE IF EXISTS account_status;
            DROP TYPE IF EXISTS user_role;
        ');
    }
};
