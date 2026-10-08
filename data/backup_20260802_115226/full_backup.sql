-- SUPABASE FULL DATABASE BACKUP
-- Generated: 2026-08-02 11:52:29

-- --- SCHEMA STRUCT ---
-- Skema Database PostgreSQL untuk Supabase
-- Database: Inventory 3

-- Settings table
CREATE TABLE IF NOT EXISTS public.settings (
    key VARCHAR(100) PRIMARY KEY,
    value JSONB
);

-- Users table
CREATE TABLE IF NOT EXISTS public.users (
    id BIGINT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin'
);

-- Sales table
CREATE TABLE IF NOT EXISTS public.sales (
    id BIGINT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    buyer_count INT DEFAULT 0,
    debt NUMERIC(15,2) DEFAULT 0,
    limit_amount NUMERIC(15,2) DEFAULT 0,
    due_days INT DEFAULT 0,
    phone VARCHAR(20)
);

-- Customers table
CREATE TABLE IF NOT EXISTS public.customers (
    id BIGINT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    ktp VARCHAR(30)
);

-- Suppliers table
CREATE TABLE IF NOT EXISTS public.suppliers (
    id BIGINT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20)
);

-- Items table
CREATE TABLE IF NOT EXISTS public.items (
    id BIGINT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) DEFAULT 'Dus',
    cost_price NUMERIC(15,2) DEFAULT 0,
    sell_price NUMERIC(15,2) DEFAULT 0,
    stock INT DEFAULT 0
);

-- Invoices table
CREATE TABLE IF NOT EXISTS public.invoices (
    id BIGINT PRIMARY KEY,
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    invoice_date DATE NOT NULL,
    sales_id BIGINT,
    sales_name VARCHAR(100),
    customer_id BIGINT,
    customer_name VARCHAR(100),
    total_dus INT DEFAULT 0,
    total_bonus INT DEFAULT 0,
    total_amount NUMERIC(15,2) DEFAULT 0,
    is_return INT DEFAULT 0,
    is_cash INT DEFAULT 0,
    notes TEXT,
    items JSONB DEFAULT '[]'::jsonb,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- RLS (Row Level Security) Policies
ALTER TABLE public.settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sales ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.suppliers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow all access to settings" ON public.settings FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to users" ON public.users FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to sales" ON public.sales FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to customers" ON public.customers FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to suppliers" ON public.suppliers FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to items" ON public.items FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to invoices" ON public.invoices FOR ALL USING (true) WITH CHECK (true);


-- --- DATA INSERTS ---

-- Data for table: app_users
INSERT INTO public."app_users" ("id", "username", "password", "name", "role") VALUES (1, 'admin', 'admin123', 'Administrator', 'admin') ON CONFLICT DO NOTHING;

-- Data for table: customers
INSERT INTO public."customers" ("id", "code", "name", "address", "phone", "ktp") VALUES (1, 'CUST-001', '1 thr / paskem', 'Jl. Pasarkemis No. 12', 08123456789, 3603001234567890) ON CONFLICT DO NOTHING;
INSERT INTO public."customers" ("id", "code", "name", "address", "phone", "ktp") VALUES (2, 'CUST-002', 'Toko Berkah Utama', 'Jl. Merdeka No. 12', 08111222333, 3603009876543210) ON CONFLICT DO NOTHING;
INSERT INTO public."customers" ("id", "code", "name", "address", "phone", "ktp") VALUES (3, 'CUST-003', 'Toko Rejeki Jaya', 'Jl. Sudirman No. 45', 08222333444, 3603005554443322) ON CONFLICT DO NOTHING;

-- Data for table: invoices
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (1, 'INV-20250106-003', '2025-06-01', 1, 'Budi Santoso', 3, 'Minimarket Sejahtera', 2, 0, 200000, 1, 0, 'Retur Barang Rusak', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (2, 'INV-20250106-002', '2025-06-01', 2, 'Ahmad Supardi', 2, 'Toko Rejeki Jaya', 5, 0, 750000, 0, 0, 'Penjualan Cash', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (3, 'INV-20250106-001', '2025-06-01', 1, 'Budi Santoso', 1, 'Toko Berkah Utama', 10, 1, 1400000, 0, 0, 'Penjualan Reguler', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (4, 'INV-20260723-497', '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 5560, 0, 12259875, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (5, 'INV-20260723-924', '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 20, 0, 0, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (6, 'INV-20260723-732', '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 20, 0, 690000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (7, 'INV-20260723-196', '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 18, 0, 390000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (8, 'INV-20260723-857', '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 18, 0, 390000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (9, 00000023916, '2025-06-01', 1, 'OFFICE', 1, '1 THR / PASKEM', 12, 0, 583000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (10, 00000023917, '2025-06-01', 1, 'OFFICE', 1, '1 THR / PASKEM', 26, 0, 630000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (11, 00000023918, '2025-06-01', 1, 'OFFICE', 1, '1 THR / PASKEM', 45, 0, 900000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (12, 00000023919, '2025-06-01', 1, 'OFFICE', 1, '1 THR / PASKEM', 65, 0, 1250000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (13, 00000023920, '2025-06-01', 1, 'OFFICE', 1, '1 THR / PASKEM', 23, 0, 510000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (14, 00000023921, '2025-06-01', 1, 'OFFICE', 1, '1 THR / PASKEM', 19, 0, 385000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (15, 00000023922, '2025-06-01', 1, 'OFFICE', 1, '1 THR / PASKEM', 9, 0, 180000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (16, 00000023923, '2025-06-01', 1, 'OFFICE', 1, '1 THR / PASKEM', 10, 0, 190000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (17, 'INV-20260723-351', '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 11, 0, 240000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (18, 'INV-20260723-750', '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 11, 0, 240000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (19, 'INV-20260723-361', '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 8, 0, 205000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (20, 00000023924, '2025-06-01', 1, 'Office', 1, '1 thr / paskem', 11, 0, 220000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (21, 00000023925, '2026-07-23', 1, 'Office', 1, '1 thr / paskem', 11, 0, 220000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (22, 00000023926, '2026-07-23', 1, 'Office', 1, '1 thr / paskem', 11, 0, 240000, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-24T03:23:51.880018+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (23, 00000023950, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 10, 0, 190000, 0, 1, 'Faktur Penjualan', '[]'::jsonb, '2026-07-25T09:04:13.447153+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (24, 00000023951, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 10, 0, 190000, 0, 1, 'Faktur Penjualan', '[{"qty":2,"bonus":0,"price":25000,"discount":0,"subtotal":50000,"item_name":"Aqua 220ml"},{"qty":3,"bonus":0,"price":30000,"discount":0,"subtotal":90000,"item_name":"Aqua 330ml"},{"qty":5,"bonus":0,"price":10000,"discount":0,"subtotal":50000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-25T09:04:35.231524+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (25, 00000023952, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 3, 0, 65000, 0, 0, 'Faktur Penjualan', '[{"qty":1,"bonus":0,"price":25000,"discount":0,"subtotal":25000,"item_name":"Aqua 220ml"},{"qty":1,"bonus":0,"price":30000,"discount":0,"subtotal":30000,"item_name":"Aqua 330ml"},{"qty":1,"bonus":0,"price":10000,"discount":0,"subtotal":10000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-25T09:05:30.118908+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (26, 00000023953, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 6, 0, 130000, 0, 0, 'Faktur Penjualan', '[{"qty":2,"bonus":0,"price":25000,"discount":0,"subtotal":50000,"item_name":"Aqua 220ml"},{"qty":2,"bonus":0,"price":30000,"discount":0,"subtotal":60000,"item_name":"Aqua 330ml"},{"qty":2,"bonus":0,"price":10000,"discount":0,"subtotal":20000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-25T09:06:33.062855+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (27, 00000023954, '2026-07-26', 1, 'Office', 1, '1 thr / paskem', 15, 0, 325000, 0, 0, 'Faktur Penjualan', '[{"qty":5,"bonus":0,"price":25000,"discount":0,"subtotal":125000,"item_name":"Aqua 220ml"},{"qty":5,"bonus":0,"price":30000,"discount":0,"subtotal":150000,"item_name":"Aqua 330ml"},{"qty":5,"bonus":0,"price":10000,"discount":0,"subtotal":50000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-25T09:08:36.297274+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (28, 00000023955, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 10, 0, 215000, 0, 0, 'Faktur Penjualan', '[{"qty":5,"bonus":0,"price":25000,"discount":0,"subtotal":125000,"item_name":"Aqua 220ml"},{"qty":2,"bonus":0,"price":30000,"discount":0,"subtotal":60000,"item_name":"Aqua 330ml"},{"qty":3,"bonus":0,"price":10000,"discount":0,"subtotal":30000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-25T14:42:48.489684+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (29, 00000023956, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 10, 0, 215000, 0, 0, 'Faktur Penjualan', '[{"qty":5,"bonus":0,"price":25000,"discount":0,"subtotal":125000,"item_name":"Aqua 220ml"},{"qty":2,"bonus":0,"price":30000,"discount":0,"subtotal":60000,"item_name":"Aqua 330ml"},{"qty":3,"bonus":0,"price":10000,"discount":0,"subtotal":30000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-25T14:47:22.533668+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (30, 00000023957, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 10, 0, 215000, 0, 0, 'Faktur Penjualan', '[{"qty":5,"bonus":0,"price":25000,"discount":0,"subtotal":125000,"item_name":"Aqua 220ml"},{"qty":2,"bonus":0,"price":30000,"discount":0,"subtotal":60000,"item_name":"Aqua 330ml"},{"qty":3,"bonus":0,"price":10000,"discount":0,"subtotal":30000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-25T14:50:46.616479+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (31, 00000023958, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 10, 0, 215000, 0, 0, 'Faktur Penjualan', '[{"qty":5,"bonus":0,"price":25000,"discount":0,"subtotal":125000,"item_name":"Aqua 220ml"},{"qty":2,"bonus":0,"price":30000,"discount":0,"subtotal":60000,"item_name":"Aqua 330ml"},{"qty":3,"bonus":0,"price":10000,"discount":0,"subtotal":30000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-25T14:53:53.404302+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (32, 00000023959, '2026-07-25', 1, 'Office', 1, '1 thr / paskem', 0, 0, 0, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-25T15:03:55.687269+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (33, 00000023960, '2026-07-28', 1, 'Office', 1, '1 thr / paskem', 0, 0, 0, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-28T02:27:03.099215+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (34, 00000023961, '2026-07-28', 1, 'Office', 1, '1 thr / paskem', 0, 0, 0, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-28T02:27:56.586589+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (35, 00000023962, '2026-07-28', 1, 'Office', 1, '1 thr / paskem', 0, 0, 0, 0, 0, 'Faktur Penjualan', '[]'::jsonb, '2026-07-28T02:33:02.517205+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (36, 00000023963, '2026-07-28', 4, 'Office', 1, '1 thr / paskem', 10, 0, 230000, 0, 0, 'Faktur Penjualan', '[{"qty":2,"bonus":0,"price":25000,"discount":0,"subtotal":50000,"item_name":"Aqua 220ml"},{"qty":5,"bonus":0,"price":30000,"discount":0,"subtotal":150000,"item_name":"Aqua 330ml"},{"qty":3,"bonus":0,"price":10000,"discount":0,"subtotal":30000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-28T02:36:59.336241+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (37, 00000023964, '2026-07-28', 4, 'Burhan', 1, '1 thr / paskem', 12, 0, 250000, 0, 0, 'Faktur Penjualan', '[{"qty":2,"bonus":0,"price":25000,"discount":0,"subtotal":50000,"item_name":"Aqua 220ml"},{"qty":5,"bonus":0,"price":30000,"discount":0,"subtotal":150000,"item_name":"Aqua 330ml"},{"qty":5,"bonus":0,"price":10000,"discount":0,"subtotal":50000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-28T02:50:01.564513+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (38, 00000023965, '2026-07-28', 6, 'kiko', 1, '1 thr / paskem', 15, 0, 425000, 0, 0, 'Faktur Penjualan', '[{"qty":5,"bonus":0,"price":25000,"discount":0,"subtotal":125000,"item_name":"Aqua 220ml"},{"qty":10,"bonus":0,"price":30000,"discount":0,"subtotal":300000,"item_name":"Aqua 330ml"}]'::jsonb, '2026-07-28T02:55:05.596543+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (39, 00000023966, '2026-07-28', 3, 'Ahmad Supardi', 1, '1 thr / paskem', 8, 0, 130000, 0, 0, 'Faktur Penjualan', '[{"qty":2,"bonus":0,"price":25000,"discount":0,"subtotal":50000,"item_name":"Aqua 220ml"},{"qty":2,"bonus":0,"price":30000,"discount":0,"subtotal":60000,"item_name":"Aqua 330ml"},{"qty":2,"bonus":0,"price":10000,"discount":0,"subtotal":20000,"item_name":"Aqua 600ml"},{"qty":2,"bonus":0,"price":0,"discount":0,"subtotal":0,"item_name":"Aqua 1500ml"}]'::jsonb, '2026-07-28T03:02:47.498697+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (40, 00000023967, '2026-07-30', 2, 'Budi Santoso', 1, '1 thr / paskem', 15, 0, 325000, 0, 0, 'Faktur Penjualan', '[{"qty":5,"bonus":0,"price":25000,"discount":0,"subtotal":125000,"item_name":"Aqua 220ml"},{"qty":5,"bonus":0,"price":30000,"discount":0,"subtotal":150000,"item_name":"Aqua 330ml"},{"qty":5,"bonus":0,"price":10000,"discount":0,"subtotal":50000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-30T06:28:23.280121+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (41, 00000023968, '2026-07-30', 4, 'Burhan', 1, '1 thr / paskem', 3, 0, 65000, 0, 0, 'Faktur Penjualan', '[{"qty":1,"bonus":0,"price":25000,"discount":0,"subtotal":25000,"item_name":"Aqua 220ml"},{"qty":1,"bonus":0,"price":30000,"discount":0,"subtotal":30000,"item_name":"Aqua 330ml"},{"qty":1,"bonus":0,"price":10000,"discount":0,"subtotal":10000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-30T06:29:01.785005+00:00') ON CONFLICT DO NOTHING;
INSERT INTO public."invoices" ("id", "invoice_no", "invoice_date", "sales_id", "sales_name", "customer_id", "customer_name", "total_dus", "total_bonus", "total_amount", "is_return", "is_cash", "notes", "items", "created_at") VALUES (42, 00000023969, '2026-07-30', 5, 'Abc', 1, '1 thr / paskem', 6, 0, 130000, 0, 0, 'Faktur Penjualan', '[{"qty":2,"bonus":0,"price":25000,"discount":0,"subtotal":50000,"item_name":"Aqua 220ml"},{"qty":2,"bonus":0,"price":30000,"discount":0,"subtotal":60000,"item_name":"Aqua 330ml"},{"qty":2,"bonus":0,"price":10000,"discount":0,"subtotal":20000,"item_name":"Aqua 600ml"}]'::jsonb, '2026-07-30T06:29:45.479257+00:00') ON CONFLICT DO NOTHING;

-- Data for table: items
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (1, 'BRG-001', 'Aqua 220ml', 'Dus', 0, 25000, 40) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (2, 'BRG-002', 'Aqua 330ml', 'Dus', 0, 30000, 11) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (3, 'BRG-003', 'Aqua 600ml', 'Dus', 0, 10000, 153) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (4, 'BRG-004', 'Aqua 1500ml', 'Dus', 0, 0, 113) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (5, 'BRG-005', 'Ale-Ale', 'Dus', 15470, 18200, 127) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (6, 'BRG-006', 'Floridina', 'Dus', 0, 0, 88) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (7, 'BRG-007', 'Golda Coffee', 'Dus', 0, 0, 415) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (8, 'BRG-008', 'Milku', 'Dus', 0, 0, 190) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (9, 'BRG-009', 'Teh Rio', 'Dus', 0, 0, 1837) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (10, 'BRG-010', 'Isoplus 350ml', 'Dus', 0, 0, 78) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (11, 'BRG-011', 'Cocacola 390ml', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (12, 'BRG-012', 'Fanta 390ml', 'Dus', 36550, 43000, 70) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (13, 'BRG-013', 'Sprite 390ml', 'Dus', 0, 0, 115) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (14, 'BRG-014', 'Nutriboost', 'Dus', 0, 0, 12) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (15, 'BRG-015', 'Pulpi Botol', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (16, 'BRG-016', 'Cincau', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (17, 'BRG-017', 'Lemineral 330ml', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (18, 'BRG-018', 'Lemineral 600ml', 'Dus', 0, 0, 115) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (19, 'BRG-019', 'Lemineral 1500ml', 'Dus', 0, 0, 92) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (20, 'BRG-020', 'Mizone', 'Dus', 0, 0, 262) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (21, 'BRG-021', 'Mount Top 220ml', 'Dus', 0, 0, 285) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (22, 'BRG-022', 'Okky Jelly', 'Dus', 0, 0, 58) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (23, 'BRG-023', 'Okky Jelly Big', 'Dus', 0, 0, 145) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (24, 'BRG-024', 'Panther', 'Dus', 0, 0, 377) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (25, 'BRG-025', 'Sari Kelapa', 'Dus', 0, 0, 81) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (26, 'BRG-026', 'Sanqua Gelas 220ml', 'Dus', 0, 0, 67) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (27, 'BRG-027', 'Sanqua 600ml', 'Dus', 0, 0, 264) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (28, 'BRG-028', 'Sanqua 1500ml', 'Dus', 0, 0, 167) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (29, 'BRG-029', 'Sanqua Botol 220ml', 'Dus', 0, 0, 49) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (30, 'BRG-030', 'Teh Pucuk 350ml', 'Dus', 47600, 56000, 123) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (31, 'BRG-031', 'Teh Gelas', 'Dus', 0, 0, 156) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (32, 'BRG-032', 'Kopikap', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (33, 'BRG-033', 'Kopi Robust', 'Dus', 0, 0, 106) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (34, 'BRG-034', 'Kopi Nongkrong', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (35, 'BRG-035', 'Kopikap Jumbo', 'Dus', 0, 0, 48) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (36, 'BRG-036', 'By Top 200 Ml', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (37, 'BRG-037', 'By Top 600 Ml', 'Dus', 14301.25, 16825, 3) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (38, 'BRG-038', 'Asem Jawa', 'Dus', 0, 0, 14) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (39, 'BRG-039', 'Nipis Gelas', 'Dus', 0, 0, 67) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (40, 'BRG-040', 'Sosro Pet 350ml', 'Dus', 0, 0, 47) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (41, 'BRG-041', 'Fruitea', 'Dus', 0, 0, 26) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (42, 'BRG-042', 'Olala', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (43, 'BRG-043', 'Power F', 'Dus', 0, 0, 41) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (44, 'BRG-044', 'Javana', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (45, 'BRG-045', 'Le Min Galon', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (46, 'BRG-046', 'Nipis Madu', 'Dus', 0, 0, 24) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (47, 'BRG-047', 'Aquviva 250 Ml', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (48, 'BRG-048', 'Aquviva 700 Ml', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (49, 'BRG-049', 'Aquviva 1600ml', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (50, 'BRG-050', 'Vit 550ml', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;
INSERT INTO public."items" ("id", "code", "name", "unit", "cost_price", "sell_price", "stock") VALUES (51, 'BRG-051', 'Vit 1500', 'Dus', 0, 0, 0) ON CONFLICT DO NOTHING;

-- Data for table: sales
INSERT INTO public."sales" ("id", "code", "name", "buyer_count", "debt", "limit_amount", "due_days", "phone") VALUES (1, 00000001, 'Office', 875, 4960000, 500000000, 0, NULL) ON CONFLICT DO NOTHING;
INSERT INTO public."sales" ("id", "code", "name", "buyer_count", "debt", "limit_amount", "due_days", "phone") VALUES (2, 00000002, 'Budi Santoso', 120, 1200000, 100000000, 7, NULL) ON CONFLICT DO NOTHING;
INSERT INTO public."sales" ("id", "code", "name", "buyer_count", "debt", "limit_amount", "due_days", "phone") VALUES (3, 00000003, 'Ahmad Supardi', 45, 750000, 50000000, 14, NULL) ON CONFLICT DO NOTHING;
INSERT INTO public."sales" ("id", "code", "name", "buyer_count", "debt", "limit_amount", "due_days", "phone") VALUES (4, 00000004, 'Burhan', 0, 0, 100000000, 0, NULL) ON CONFLICT DO NOTHING;
INSERT INTO public."sales" ("id", "code", "name", "buyer_count", "debt", "limit_amount", "due_days", "phone") VALUES (5, 00000005, 'Abc', 0, 0, 100000000, 0, NULL) ON CONFLICT DO NOTHING;
INSERT INTO public."sales" ("id", "code", "name", "buyer_count", "debt", "limit_amount", "due_days", "phone") VALUES (6, 00000006, 'kiko', 0, 0, 100000000, 0, NULL) ON CONFLICT DO NOTHING;

-- Data for table: settings
INSERT INTO public."settings" ("key", "value") VALUES ('main_settings', '{"company_name":"KAYOLA JAYA","default_date":"2026-07-23","company_phone":"Telp. 081806366565","auto_today_date":1,"company_address":"Jl. Raya Pasar Kemis","default_payment":"CASH","default_hormat_kami":"vieri","last_invoice_number":23969}'::jsonb) ON CONFLICT DO NOTHING;

-- Data for table: suppliers
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (1, 00000043, 'Coca Cola', 'Jakarta', '021-111111') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (2, 00000047, 'Edi Jaya', 'Tangerang', '021-222222') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (3, 00000040, 'Glory Jaya', 'Jakarta', '021-333333') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (4, 00000048, 'Kevin Sumber Air', 'Bekasi', '021-444444') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (5, 00000045, 'Laris Jaya', 'Bogor', '021-555555') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (6, 00000046, 'Mayora', 'Tangerang', '021-666666') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (7, 00000042, 'Neglasari', 'Tangerang', '021-777777') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (8, 00000051, 'Ronald', 'Jakarta', '021-888888') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (9, 00000050, 'Sosro', 'Jakarta', '021-999999') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (10, 00000049, 'Tiga Sodara', 'Serang', '021-000000') ON CONFLICT DO NOTHING;
INSERT INTO public."suppliers" ("id", "code", "name", "address", "phone") VALUES (11, 00000044, 'Wings Foods ( Ale Rio )', 'Jakarta', '021-123123') ON CONFLICT DO NOTHING;

