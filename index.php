<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'expense_tracker';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_expense':
                $stmt = $pdo->prepare("INSERT INTO expenses (category, amount, description, date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['category'], $_POST['amount'], $_POST['description'], $_POST['date']]);
                break;
            
            case 'set_budget':
                $stmt = $pdo->prepare("INSERT INTO budgets (category, amount, month) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = ?");
                $stmt->execute([$_POST['category'], $_POST['budget_amount'], $_POST['month'], $_POST['budget_amount']]);
                break;
            
            case 'delete_expense':
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
                $stmt->execute([$_POST['expense_id']]);
                break;
        }
        header('Location: index.php');
        exit;
    }
}

// Fetch data
$current_month = date('Y-m');
$expenses = $pdo->query("SELECT * FROM expenses ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);
$budgets = $pdo->query("SELECT * FROM budgets WHERE month = '$current_month'")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals by category
$category_totals = [];
$stmt = $pdo->query("SELECT category, SUM(amount) as total FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = '$current_month' GROUP BY category");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $category_totals[$row['category']] = $row['total'];
}

$total_spent = array_sum($category_totals);
$total_budget = 0;
foreach ($budgets as $budget) {
    $total_budget += $budget['amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Expense Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeInDown 0.6s ease;
        }

        .header h1 {
            font-size: 3em;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: fadeInUp 0.6s ease;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2em;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 15px;
        }

        .stat-box h3 {
            font-size: 2.5em;
            margin-bottom: 5px;
        }

        .stat-box p {
            opacity: 0.9;
            font-size: 0.9em;
        }

        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.6s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8em;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            padding: 8px 15px;
            font-size: 0.9em;
            width: auto;
        }

        .expense-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .expense-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }

        .expense-item:hover {
            background: #e9ecef;
        }

        .expense-details {
            flex: 1;
        }

        .expense-category {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .category-food { background: #ffeaa7; color: #fdcb6e; }
        .category-transport { background: #74b9ff; color: #0984e3; }
        .category-entertainment { background: #fab1a0; color: #e17055; }
        .category-utilities { background: #a29bfe; color: #6c5ce7; }
        .category-shopping { background: #fd79a8; color: #e84393; }
        .category-health { background: #55efc4; color: #00b894; }
        .category-other { background: #dfe6e9; color: #636e72; }

        .expense-amount {
            font-size: 1.3em;
            font-weight: 700;
            color: #667eea;
        }

        .budget-overview {
            margin-top: 20px;
        }

        .budget-item {
            margin-bottom: 20px;
        }

        .budget-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: 600;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Smart Expense Tracker</h1>
            <p>Manage your finances intelligently</p>
        </div>

        <div class="dashboard">
            <!-- Summary Card -->
            <div class="card">
                <h2><span class="card-icon">📊</span> Monthly Overview</h2>
                <div class="stat-box">
                    <h3>$<?php echo number_format($total_spent, 2); ?></h3>
                    <p>Total Spent This Month</p>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                    <h3>$<?php echo number_format($total_budget, 2); ?></h3>
                    <p>Total Budget</p>
                </div>
                <?php if ($total_budget > 0): ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(($total_spent / $total_budget) * 100, 100); ?>%">
                            <?php echo round(($total_spent / $total_budget) * 100); ?>%
                        </div>
                    </div>
                    <p style="text-align: center; margin-top: 10px; color: #666;">
                        <?php 
                        $remaining = $total_budget - $total_spent;
                        echo $remaining >= 0 ? "$" . number_format($remaining, 2) . " remaining" : "$" . number_format(abs($remaining), 2) . " over budget";
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Add Expense Card -->
            <div class="card">
                <h2><span class="card-icon">➕</span> Add Expense</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_expense">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="Food">🍔 Food</option>
                            <option value="Transport">🚗 Transport</option>
                            <option value="Entertainment">🎮 Entertainment</option>
                            <option value="Utilities">💡 Utilities</option>
                            <option value="Shopping">🛍️ Shopping</option>
                            <option value="Health">⚕️ Health</option>
                            <option value="Other">📌 Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount ($)</label>
                        <input type="number" step="0.01" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" class="btn">Add Expense</button>
                </form>
            </div>

            <!-- Set Budget Card -->
            <div class="card">
                <h2><span class="card-icon">🎯</span> Set Budget</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="set_budget">
                    <input type="hidden" name="month" value="<?php echo $current_month; ?>">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="Food">🍔 Food</option>
                            <option value="Transport">🚗 Transport</option>
                            <option value="Entertainment">🎮 Entertainment</option>
                            <option value="Utilities">💡 Utilities</option>
                            <option value="Shopping">🛍️ Shopping</option>
                            <option value="Health">⚕️ Health</option>
                            <option value="Other">📌 Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Budget Amount ($)</label>
                        <input type="number" step="0.01" name="budget_amount" required>
                    </div>
                    <button type="submit" class="btn">Set Budget</button>
                </form>

                <?php if (!empty($budgets)): ?>
                <div class="budget-overview">
                    <h3 style="margin-bottom: 15px; color: #667eea;">Current Budgets</h3>
                    <?php foreach ($budgets as $budget): ?>
                        <?php 
                        $spent = $category_totals[$budget['category']] ?? 0;
                        $percentage = ($spent / $budget['amount']) * 100;
                        ?>
                        <div class="budget-item">
                            <div class="budget-header">
                                <span><?php echo $budget['category']; ?></span>
                                <span>$<?php echo number_format($spent, 2); ?> / $<?php echo number_format($budget['amount'], 2); ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%; background: <?php echo $percentage > 100 ? 'linear-gradient(90deg, #f093fb, #f5576c)' : 'linear-gradient(90deg, #667eea, #764ba2)'; ?>">
                                    <?php echo round($percentage); ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Expenses Card -->
        <div class="card">
            <h2><span class="card-icon">📝</span> Recent Expenses</h2>
            <div class="expense-list">
                <?php if (empty($expenses)): ?>
                    <div class="empty-state">
                        <p>No expenses recorded yet. Start tracking your spending!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($expenses as $expense): ?>
                        <div class="expense-item">
                            <div class="expense-details">
                                <span class="expense-category category-<?php echo strtolower($expense['category']); ?>">
                                    <?php echo $expense['category']; ?>
                                </span>
                                <div><strong><?php echo htmlspecialchars($expense['description']); ?></strong></div>
                                <div style="font-size: 0.9em; color: #666;"><?php echo date('M d, Y', strtotime($expense['date'])); ?></div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="expense-amount">$<?php echo number_format($expense['amount'], 2); ?></div>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete_expense">
                                    <input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>">
                                    <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this expense?')">🗑️</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
