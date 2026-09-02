-- Smart Expense Tracker Database Setup
-- Run this SQL script to create the database and tables

CREATE DATABASE IF NOT EXISTS expense_tracker;
USE expense_tracker;

-- Expenses Table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Budgets Table
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    month VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_category_month (category, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data (optional)
INSERT INTO expenses (category, amount, description, date) VALUES
('Food', 45.50, 'Grocery shopping at Walmart', '2026-01-28'),
('Transport', 25.00, 'Gas refill', '2026-01-27'),
('Entertainment', 15.99, 'Netflix subscription', '2026-01-26'),
('Food', 32.75, 'Dinner at restaurant', '2026-01-25'),
('Shopping', 89.99, 'New shoes', '2026-01-24');

INSERT INTO budgets (category, amount, month) VALUES
('Food', 500.00, '2026-01'),
('Transport', 200.00, '2026-01'),
('Entertainment', 100.00, '2026-01'),
('Shopping', 300.00, '2026-01'),
('Utilities', 150.00, '2026-01');
