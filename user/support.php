<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle ticket creation
if ($_POST && isset($_POST['create_ticket'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $priority = sanitize($_POST['priority'] ?? 'medium');
        $category = sanitize($_POST['category'] ?? 'general');
        
        if (empty($subject) || empty($message)) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($subject) < 5) {
            $error = 'Subject must be at least 5 characters long.';
        } elseif (strlen($message) < 10) {
            $error = 'Message must be at least 10 characters long.';
        } else {
            try {
                $db->beginTransaction();
                
                // Generate unique ticket number
                $ticket_number = 'UH' . date('Y') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                
                // Check if ticket number already exists
                $stmt = $db->prepare("SELECT id FROM support_tickets WHERE ticket_number = ?");
                $stmt->execute([$ticket_number]);
                while ($stmt->fetch()) {
                    $ticket_number = 'UH' . date('Y') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    $stmt->execute([$ticket_number]);
                }
                
                // Create ticket
                $stmt = $db->prepare("
                    INSERT INTO support_tickets (ticket_number, user_id, subject, message, priority, category) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$ticket_number, $user_id, $subject, $message, $priority, $category]);
                $ticket_id = $db->lastInsertId();
                
                $db->commit();
                $success = "Support ticket #{$ticket_number} created successfully. We'll get back to you within 24 hours.";
                
                // Send notification to user
                sendNotification($user_id, 'Support Ticket Created', "Your support ticket #{$ticket_number} has been created successfully.", 'info');
                
                // Clear form data
                $_POST = [];
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to create support ticket. Please try again.';
                error_log("Support ticket creation error: " . $e->getMessage());
            }
        }
    }
}

// Get user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's tickets with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM support_tickets 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$total_tickets = $stmt->fetch()['total'];
$total_pages = ceil($total_tickets / $limit);

$stmt = $db->prepare("
    SELECT st.*, 
           CASE 
               WHEN st.responded_by IS NOT NULL THEN u.full_name 
               ELSE NULL 
           END as admin_name
    FROM support_tickets st
    LEFT JOIN users u ON st.responded_by = u.id
    WHERE st.user_id = ?
    ORDER BY st.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $limit, $offset]);
$tickets = $stmt->fetchAll();

// Get ticket statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_tickets,
        COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets
    FROM support_tickets 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Center - Ultra Harvest Global</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Poppins', sans-serif; }
        
        .glass-card {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .support-section {
            display: none;
        }
        
        .support-section.active {
            display: block;
        }
        
        .nav-item.active {
            background: linear-gradient(45deg, #10b981, #34d399);
            color: white;
        }
        
        .ticket-card {
            transition: all 0.3s ease;
        }
        
        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .priority-urgent { border-left: 4px solid #ef4444; }
        .priority-high { border-left: 4px solid #f97316; }
        .priority-medium { border-left: 4px solid #eab308; }
        .priority-low { border-left: 4px solid #22c55e; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">

    <!-- Header -->
    <header class="bg-gray-800/50 backdrop-blur-md border-b border-gray-700 sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/user/dashboard.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full overflow-hidden" style="background: linear-gradient(45deg, #10b981, #fbbf24);">
                            <img src="/ultra%20Harvest%20Logo.jpg" alt="Ultra Harvest Global" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                        </div>
                    </a>
                    
                    <nav class="hidden md:flex space-x-6">
                        <a href="/user/dashboard.php" class="text-gray-300 hover:text-emerald-400 transition">Dashboard</a>
                        <a href="/user/packages.php" class="text-gray-300 hover:text-emerald-400 transition">Packages</a>
                        <a href="/user/transactions.php" class="text-gray-300 hover:text-emerald-400 transition">Transactions</a>
                        <a href="/user/referrals.php" class="text-gray-300 hover:text-emerald-400 transition">Referrals</a>
                    </nav>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="/help.php" class="text-gray-400 hover:text-white" title="Help Center">
                        <i class="fas fa-question-circle text-xl"></i>
                    </a>
                    <a href="/user/dashboard.php" class="text-gray-400 hover:text-white">
                        <i class="fas fa-home text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        
        <!-- Page Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-2">
                <i class="fas fa-headset text-blue-400 mr-3"></i>
                Support Center
            </h1>
            <p class="text-xl text-gray-300">Get help and create support tickets</p>
        </div>

        <!-- Quick Help Links -->
        <section class="mb-8">
            <div class="glass-card rounded-xl p-6">
                <h2 class="text-xl font-bold text-white mb-4">Quick Help</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="/help.php#deposits" class="flex items-center p-4 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 transition">
                        <i class="fas fa-arrow-down text-emerald-400 mr-3 text-xl"></i>
                        <div>
                            <h3 class="font-medium text-white">Deposit Help</h3>
                            <p class="text-xs text-gray-400">M-Pesa deposit guide</p>
                        </div>
                    </a>
                    
                    <a href="/help.php#withdrawals" class="flex items-center p-4 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 transition">
                        <i class="fas fa-arrow-up text-red-400 mr-3 text-xl"></i>
                        <div>
                            <h3 class="font-medium text-white">Withdrawal Help</h3>
                            <p class="text-xs text-gray-400">How to withdraw funds</p>
                        </div>
                    </a>
                    
                    <a href="/help.php#packages" class="flex items-center p-4 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 transition">
                        <i class="fas fa-chart-line text-yellow-400 mr-3 text-xl"></i>
                        <div>
                            <h3 class="font-medium text-white">Trading Packages</h3>
                            <p class="text-xs text-gray-400">How packages work</p>
                        </div>
                    </a>
                    
                    <a href="https://wa.me/254700000000" target="_blank" class="flex items-center p-4 bg-gray-800/50 rounded-lg hover:bg-gray-700/50 transition">
                        <i class="fab fa-whatsapp text-green-400 mr-3 text-xl"></i>
                        <div>
                            <h3 class="font-medium text-white">WhatsApp</h3>
                            <p class="text-xs text-gray-400">Chat with us now</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-400 mr-2"></i>
                <span class="text-red-300"><?php echo htmlspecialchars($error); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="mb-6 p-4 bg-emerald-500/20 border border-emerald-500/50 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-emerald-400 mr-2"></i>
                <span class="text-emerald-300"><?php echo htmlspecialchars($success); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-4 gap-8">
            
            <!-- Support Navigation -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Support Menu</h3>
                    <nav class="space-y-2">
                        <button onclick="showSection('create')" class="nav-item w-full text-left px-4 py-3 rounded-lg transition active" data-section="create">
                            <i class="fas fa-plus mr-3"></i>Create Ticket
                        </button>
                        <button onclick="showSection('tickets')" class="nav-item w-full text-left px-4 py-3 rounded-lg transition" data-section="tickets">
                            <i class="fas fa-ticket-alt mr-3"></i>My Tickets
                        </button>
                        <button onclick="showSection('faq')" class="nav-item w-full text-left px-4 py-3 rounded-lg transition" data-section="faq">
                            <i class="fas fa-question-circle mr-3"></i>FAQ
                        </button>
                        <a href="/help.php" class="nav-item w-full text-left px-4 py-3 rounded-lg transition hover:bg-gray-700">
                            <i class="fas fa-book mr-3"></i>Help Center
                        </a>
                    </nav>
                </div>

                <!-- Ticket Statistics -->
                <div class="glass-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-bold text-white mb-4">Your Tickets</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Total</span>
                            <span class="text-white font-bold"><?php echo $stats['total_tickets']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Open</span>
                            <span class="text-yellow-400 font-bold"><?php echo $stats['open_tickets']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">In Progress</span>
                            <span class="text-blue-400 font-bold"><?php echo $stats['in_progress_tickets']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Resolved</span>
                            <span class="text-emerald-400 font-bold"><?php echo $stats['resolved_tickets']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Content -->
            <div class="lg:col-span-3">
                
                <!-- Create Ticket Section -->
                <div id="create-section" class="support-section active">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-xl font-bold text-white mb-6">Create Support Ticket</h3>
                        
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="create_ticket" value="1">
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Category *</label>
                                    <select name="category" class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-emerald-500 focus:outline-none" required>
                                        <option value="general" <?php echo ($_POST['category'] ?? '') === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                                        <option value="technical" <?php echo ($_POST['category'] ?? '') === 'technical' ? 'selected' : ''; ?>>Technical Issue</option>
                                        <option value="billing" <?php echo ($_POST['category'] ?? '') === 'billing' ? 'selected' : ''; ?>>Billing/Payment</option>
                                        <option value="account" <?php echo ($_POST['category'] ?? '') === 'account' ? 'selected' : ''; ?>>Account Issues</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Priority</label>
                                    <select name="priority" class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-emerald-500 focus:outline-none">
                                        <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                                        <option value="urgent" <?php echo ($_POST['priority'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Subject *</label>
                                <input type="text" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                                       class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-emerald-500 focus:outline-none"
                                       placeholder="Brief description of your issue" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Message *</label>
                                <textarea name="message" rows="6"
                                          class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:border-emerald-500 focus:outline-none"
                                          placeholder="Please describe your issue in detail..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                                <h4 class="font-bold text-blue-400 mb-2">Tips for Better Support:</h4>
                                <ul class="text-sm text-gray-300 space-y-1">
                                    <li>• Be specific about the issue you're experiencing</li>
                                    <li>• Include any error messages you've seen</li>
                                    <li>• Mention what you were trying to do when the issue occurred</li>
                                    <li>• Include relevant transaction IDs or amounts if applicable</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition">
                                <i class="fas fa-paper-plane mr-2"></i>Submit Ticket
                            </button>
                        </form>
                    </div>
                </div>

                <!-- My Tickets Section -->
                <div id="tickets-section" class="support-section">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-xl font-bold text-white mb-6">My Support Tickets</h3>
                        
                        <?php if (empty($tickets)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-ticket-alt text-6xl text-gray-600 mb-4"></i>
                                <h4 class="text-xl font-bold text-gray-400 mb-2">No Support Tickets</h4>
                                <p class="text-gray-500 mb-6">You haven't created any support tickets yet</p>
                                <button onclick="showSection('create')" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition">
                                    <i class="fas fa-plus mr-2"></i>Create First Ticket
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($tickets as $ticket): ?>
                                <div class="ticket-card p-6 bg-gray-800/50 rounded-lg priority-<?php echo $ticket['priority']; ?>">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <h4 class="text-lg font-bold text-white mb-1">
                                                #<?php echo $ticket['ticket_number']; ?> - <?php echo htmlspecialchars($ticket['subject']); ?>
                                            </h4>
                                            <div class="flex items-center space-x-4 text-sm text-gray-400">
                                                <span>
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-tag mr-1"></i>
                                                    <?php echo ucfirst($ticket['category']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                                <?php 
                                                echo match($ticket['priority']) {
                                                    'urgent' => 'bg-red-500/20 text-red-300',
                                                    'high' => 'bg-orange-500/20 text-orange-300',
                                                    'medium' => 'bg-yellow-500/20 text-yellow-300',
                                                    'low' => 'bg-green-500/20 text-green-300',
                                                };
                                                ?>">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                                <?php 
                                                echo match($ticket['status']) {
                                                    'closed' => 'bg-gray-500/20 text-gray-300',
                                                    'resolved' => 'bg-green-500/20 text-green-300',
                                                    'in_progress' => 'bg-blue-500/20 text-blue-300',
                                                    default => 'bg-yellow-500/20 text-yellow-300',
                                                };
                                                ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-300 mb-4"><?php echo htmlspecialchars(substr($ticket['message'], 0, 200)) . (strlen($ticket['message']) > 200 ? '...' : ''); ?></p>
                                    
                                    <?php if ($ticket['admin_response']): ?>
                                        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 mb-4">
                                            <div class="flex items-center mb-2">
                                                <i class="fas fa-user-tie text-emerald-400 mr-2"></i>
                                                <span class="font-medium text-emerald-300">
                                                    Response from <?php echo htmlspecialchars($ticket['admin_name'] ?? 'Support Team'); ?>
                                                </span>
                                            </div>
                                            <p class="text-gray-300"><?php echo htmlspecialchars($ticket['admin_response']); ?></p>
                                            <?php if ($ticket['updated_at'] !== $ticket['created_at']): ?>
                                                <p class="text-xs text-gray-400 mt-2">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Responded on <?php echo date('M j, Y g:i A', strtotime($ticket['updated_at'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center justify-between text-xs text-gray-400">
                                        <div>
                                            Ticket ID: <?php echo $ticket['ticket_number']; ?>
                                        </div>
                                        <?php if ($ticket['status'] === 'open'): ?>
                                            <div class="text-yellow-400">
                                                <i class="fas fa-clock mr-1"></i>
                                                Waiting for response
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <div class="flex items-center justify-center mt-8 space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>" class="px-4 py-2 bg-gray-800 text-gray-300 rounded-lg hover:bg-gray-700 transition">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" 
                                       class="px-4 py-2 rounded-lg transition <?php echo $i === $page ? 'bg-emerald-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>" class="px-4 py-2 bg-gray-800 text-gray-300 rounded-lg hover:bg-gray-700 transition">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div id="faq-section" class="support-section">
                    <div class="glass-card rounded-xl p-6">
                        <h3 class="text-xl font-bold text-white mb-6">Frequently Asked Questions</h3>
                        
                        <div class="space-y-4">
                            <div class="bg-gray-800/50 rounded-lg p-4">
                                <h4 class="font-bold text-white mb-2 cursor-pointer flex items-center justify-between" onclick="toggleFaq(this)">
                                    How long does it take to process withdrawals?
                                    <i class="fas fa-chevron-down transform transition-transform"></i>
                                </h4>
                                <div class="faq-content hidden">
                                    <p class="text-gray-300">Withdrawals are typically processed within 24 hours during business days. The funds will be sent directly to your M-Pesa account.</p>
                                </div>
                            </div>
                            
                            <div class="bg-gray-800/50 rounded-lg p-4">
                                <h4 class="font-bold text-white mb-2 cursor-pointer flex items-center justify-between" onclick="toggleFaq(this)">
                                    What is the minimum deposit amount?
                                    <i class="fas fa-chevron-down transform transition-transform"></i>
                                </h4>
                                <div class="faq-content hidden">
                                    <p class="text-gray-300">The minimum deposit amount is KSh 100. You can deposit using M-Pesa through our secure payment gateway.</p>
                                </div>
                            </div>
                            
                            <div class="bg-gray-800/50 rounded-lg p-4">
                                <h4 class="font-bold text-white mb-2 cursor-pointer flex items-center justify-between" onclick="toggleFaq(this)">
                                    How do trading packages work?
                                    <i class="fas fa-chevron-down transform transition-transform"></i>
                                </h4>
                                <div class="faq-content hidden">
                                    <p class="text-gray-300">Trading packages are investment plans with predetermined ROI percentages and durations. When you invest in a package, you'll receive your principal plus profits when the package matures.</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-800/50 rounded-lg p-4">
                        <h4 class="font-bold text-white mb-2 cursor-pointer flex items-center justify-between" onclick="toggleFaq(this)">
                                    How does the referral program work?
                                    <i class="fas fa-chevron-down transform transition-transform"></i>
                                </h4>
                                <div class="faq-content hidden">
                                    <p class="text-gray-300">When someone registers using your referral code and makes deposits, you earn commission. Level 1 referrals earn you 10% commission, and Level 2 referrals earn 5%.</p>
                                </div>
                            </div>
                            
                            <div class="bg-gray-800/50 rounded-lg p-4">
                                <h4 class="font-bold text-white mb-2 cursor-pointer flex items-center justify-between" onclick="toggleFaq(this)">
                                    Is my money safe with Ultra Harvest?
                                    <i class="fas fa-chevron-down transform transition-transform"></i>
                                </h4>
                                <div class="faq-content hidden">
                                    <p class="text-gray-300">Yes, we use bank-level security measures to protect your funds. All transactions are encrypted and we maintain adequate reserves to honor all withdrawals.</p>
                                </div>
                            </div>
                            
                            <div class="bg-gray-800/50 rounded-lg p-4">
                                <h4 class="font-bold text-white mb-2 cursor-pointer flex items-center justify-between" onclick="toggleFaq(this)">
                                    Can I cancel a package investment?
                                    <i class="fas fa-chevron-down transform transition-transform"></i>
                                </h4>
                                <div class="faq-content hidden">
                                    <p class="text-gray-300">Package investments cannot be cancelled once activated. However, you can contact support within 24 hours of investment for special consideration in exceptional cases.</p>
                                </div>
                            </div>
                        
                        <div class="mt-8 text-center">
                            <p class="text-gray-400 mb-4">Still have questions?</p>
                            <button onclick="showSection('create')" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition">
                                <i class="fas fa-plus mr-2"></i>Create Support Ticket
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <section class="mt-12">
            <div class="glass-card rounded-xl p-8">
                <h2 class="text-2xl font-bold text-white mb-6 text-center">Other Ways to Reach Us</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fab fa-whatsapp text-green-400 text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-white mb-2">WhatsApp Support</h3>
                        <p class="text-gray-400 text-sm mb-4">Chat with us instantly</p>
                        <a href="https://wa.me/254700000000" target="_blank" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                            Start Chat
                        </a>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-envelope text-blue-400 text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-white mb-2">Email Support</h3>
                        <p class="text-gray-400 text-sm mb-4">support@ultraharvest.com</p>
                        <a href="mailto:support@ultraharvest.com" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                            Send Email
                        </a>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-book text-purple-400 text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-white mb-2">Help Center</h3>
                        <p class="text-gray-400 text-sm mb-4">Comprehensive guides</p>
                        <a href="/help.php" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
                            Browse Help
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Mobile Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-gray-800 border-t border-gray-700 md:hidden">
        <div class="grid grid-cols-4 py-2">
            <a href="/user/dashboard.php" class="flex flex-col items-center py-2 text-gray-400">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs">Home</span>
            </a>
            <a href="/user/packages.php" class="flex flex-col items-center py-2 text-gray-400">
                <i class="fas fa-chart-line text-xl mb-1"></i>
                <span class="text-xs">Trade</span>
            </a>
            <a href="/user/transactions.php" class="flex flex-col items-center py-2 text-gray-400">
                <i class="fas fa-receipt text-xl mb-1"></i>
                <span class="text-xs">History</span>
            </a>
            <a href="/user/support.php" class="flex flex-col items-center py-2 text-blue-400">
                <i class="fas fa-headset text-xl mb-1"></i>
                <span class="text-xs">Support</span>
            </a>
        </div>
    </div>

    <script>
        // Show/hide support sections
        function showSection(sectionName) {
            document.querySelectorAll('.support-section').forEach(section => {
                section.classList.remove('active');
            });
            
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.getElementById(sectionName + '-section').classList.add('active');
            document.querySelector(`[data-section="${sectionName}"]`).classList.add('active');
        }

        // Toggle FAQ items
        function toggleFaq(element) {
            const content = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const subject = document.querySelector('input[name="subject"]').value.trim();
            const message = document.querySelector('textarea[name="message"]').value.trim();
            
            if (subject.length < 5) {
                e.preventDefault();
                alert('Subject must be at least 5 characters long.');
                return;
            }
            
            if (message.length < 10) {
                e.preventDefault();
                alert('Message must be at least 10 characters long.');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
            submitBtn.disabled = true;
        });

        // Character counter for message
        const messageTextarea = document.querySelector('textarea[name="message"]');
        if (messageTextarea) {
            const charCounter = document.createElement('div');
            charCounter.className = 'text-xs text-gray-500 mt-1 text-right';
            messageTextarea.parentNode.appendChild(charCounter);
            
            function updateCharCount() {
                const current = messageTextarea.value.length;
                charCounter.textContent = `${current} characters (minimum 10)`;
                charCounter.className = current >= 10 ? 'text-xs text-emerald-400 mt-1 text-right' : 'text-xs text-gray-500 mt-1 text-right';
            }
            
            messageTextarea.addEventListener('input', updateCharCount);
            updateCharCount();
        }

        // Auto-expand textarea
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        if (messageTextarea) {
            messageTextarea.addEventListener('input', function() {
                autoResize(this);
            });
        }

        // Initialize with create section active
        document.addEventListener('DOMContentLoaded', function() {
            showSection('create');
        });
    </script>
</body>
</html>