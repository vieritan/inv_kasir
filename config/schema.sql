-- Skema Database PostgreSQL untuk Supabase
-- Database: Inventory 3

-- Settings table
CREATE TABLE IF NOT EXISTS public.settings (
    key VARCHAR(100) PRIMARY KEY,
    value JSONB
);

-- Users table
CREATE TABLE IF NOT EXISTS public.app_users (
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
ALTER TABLE public.app_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sales ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.suppliers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.invoices ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow all access to settings" ON public.settings FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to app_users" ON public.app_users FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to sales" ON public.sales FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to customers" ON public.customers FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to suppliers" ON public.suppliers FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to items" ON public.items FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all access to invoices" ON public.invoices FOR ALL USING (true) WITH CHECK (true);
