<?php
/**
 * M-Pesa Integration Test File
 * Use this to test your M-Pesa configuration
 */

require_once '../config/database.php';
require_once '../config/mpesa.php';

// Only allow admin access
requireAdmin();

$test_results = [];
$error = '';
$success = '';

if ($_POST) {
    $test_type = $_POST['test_type'] ?? '';
    
    switch ($test_type) {
        case 'connection':
            $mpesa = new MpesaIntegration();
            $result = $mpesa->testConnection();
            $test_results['connection'] = $result;
            
            if ($result['success']) {
                $success = 'M-Pesa connection test successful!';
            } else {
                $error = 'M-Pesa connection test failed: ' . $result['message'];
            }
            break;
            
        case 'stk_push':
            $phone = $_POST['test_phone'] ?? '';
            $amount = (float)($_POST['test_amount'] ?? 1);
            
            if (empty($phone) || $amount <= 0) {
                $error = 'Please provide valid phone number and amount';
            } else {
                $mpesa = new MpesaIntegration();
                
                // Create a test transaction
                $stmt = $db->prepare("
                    INSERT INTO transactions (user_id, type, amount, status, description, phone_number) 
                    VALUES (?, 'deposit', ?, 'pending', 'Test M-Pesa payment', ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $amount, $phone]);
                $test_transaction_id = $db->lastInsertId();
                
                $result = initiateMpesaPayment($phone, $amount, $test_transaction_id, 'Test Payment - Ultra Harvest');
                $test_results['stk_push'] = $result;
                
                if ($result['success']) {
                    $success = 'STK Push sent successfully! Check your phone for the payment prompt.';
                } else {
                    $error = 'STK Push failed: ' . $result['message'];
                    
                    // Clean up the test transaction
                    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
                    $stmt->execute([$test_transaction_id]);
                }
            }
            break;
            
        case 'callback_test':
            // Simulate a callback
            $test_callback = [
                'Body' => [
                    'stkCallback' => [
                        'MerchantRequestID' => 'test-merchant-' . time(),
                        'CheckoutRequestID' => 'test-checkout-' . time(),
                        'ResultCode' => 0,
                        'ResultDesc' => 'The service request is processed successfully.',
                        'CallbackMetadata' => [
                            'Item' => [
                                ['Name' => 'Amount', 'Value' => 1],
                                ['Name' => 'MpesaReceiptNumber', 'Value' => 'TEST' . time()],
                                ['Name' => 'TransactionDate', 'Value' => date('YmdHis')],
                                ['Name' => 'PhoneNumber', 'Value' => '254712345678']
                            ]
                        ]
                    ]
                ]
            ];
            
            $test_results['callback'] = $test_callback;
            $success = 'Callback test data generated. This would normally be sent by M-Pesa.';
            break;
    }
}

// Get current M-Pesa settings
$settings = [
    'consumer_key' => getSystemSetting('mpesa_consumer_key', ''),
    'consumer_secret' => getSystemSetting('mpesa_consumer_secret', ''),
    'shortcode' => getSystemSetting('mpesa_shortcode', ''),
    'passkey' => getSystemSetting('mpesa_passkey', ''),
    'environment' => getSystemSetting('mpesa_environment', 'sandbox')
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Test - Ultra Harvest Admin</title>
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
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">

    <!-- Header -->
    <header class="bg-gray-800/50 backdrop-blur-md border-b border-gray-700">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-emerald-500 to-yellow-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-seedling text-white"></i>
                        </div>
                        <div>
                            <span class="text-xl font-bold bg-gradient-to-r from-emerald-400 to-yellow-400 bg-clip-text text-transparent">Ultra Harvest</span>
                            <p class="text-xs text-gray-400">M-Pesa Test</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="/admin/" class="text-gray-400 hover:text-white">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Admin
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">M-Pesa Integration Test</h1>
            <p class="text-gray-400">Test your M-Pesa configuration and functionality</p>
        </div>

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

        <!-- Current Configuration -->
        <section class="glass-card rounded-xl p-6 mb-8">
            <h2 class="text-xl font-bold text-white mb-4">Current M-Pesa Configuration</h2>
            
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-400 text-sm">Consumer Key</p>
                    <p class="text-white font-mono"><?php echo $settings['consumer_key'] ? substr($settings['consumer_key'], 0, 20) . '...' : 'Not Set'; ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Consumer Secret</p>
                    <p class="text-white font-mono"><?php echo $settings['consumer_secret'] ? substr($settings['consumer_secret'], 0, 20) . '...' : 'Not Set'; ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Business Shortcode</p>
                    <p class="text-white font-mono"><?php echo $settings['shortcode'] ?: 'Not Set'; ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">Environment</p>
                    <p class="text-white font-mono"><?php echo ucfirst($settings['environment']); ?></p>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="text-gray-400 text-sm">Callback URL</p>
                <p class="text-white font-mono text-sm"><?php echo SITE_URL . 'api/mpesa-callback.php'; ?></p>
            </div>
        </section>

        <!-- Test Functions -->
        <div class="grid lg:grid-cols-3 gap-8">
            
            <!-- Connection Test -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-plug text-blue-400 mr-2"></i>Connection Test
                </h3>
                <p class="text-gray-400 text-sm mb-4">Test if your M-Pesa credentials are valid and can obtain an access token.</p>
                
                <form method="POST">
                    <input type="hidden" name="test_type" value="connection">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                        <i class="fas fa-play mr-2"></i>Test Connection
                    </button>
                </form>
                
                <?php if (isset($test_results['connection'])): ?>
                <div class="mt-4 p-3 bg-gray-800/50 rounded-lg">
                    <pre class="text-xs text-gray-300 overflow-auto"><?php echo json_encode($test_results['connection'], JSON_PRETTY_PRINT); ?></pre>
                </div>
                <?php endif; ?>
            </div>

            <!-- STK Push Test -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-mobile-alt text-green-400 mr-2"></i>STK Push Test
                </h3>
                <p class="text-gray-400 text-sm mb-4">Send a test payment request to a phone number.</p>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="test_type" value="stk_push">
                    
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Phone Number</label>
                        <input 
                            type="text" 
                            name="test_phone" 
                            placeholder="254712345678"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded text-white focus:border-emerald-500 focus:outline-none"
                            required
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Amount (KSh)</label>
                        <input 
                            type="number" 
                            name="test_amount" 
                            value="1"
                            min="1"
                            class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded text-white focus:border-emerald-500 focus:outline-none"
                            required
                        >
                    </div>
                    
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                        <i class="fas fa-paper-plane mr-2"></i>Send STK Push
                    </button>
                </form>
                
                <?php if (isset($test_results['stk_push'])): ?>
                <div class="mt-4 p-3 bg-gray-800/50 rounded-lg">
                    <pre class="text-xs text-gray-300 overflow-auto"><?php echo json_encode($test_results['stk_push'], JSON_PRETTY_PRINT); ?></pre>
                </div>
                <?php endif; ?>
            </div>

            <!-- Callback Test -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">
                    <i class="fas fa-exchange-alt text-purple-400 mr-2"></i>Callback Test
                </h3>
                <p class="text-gray-400 text-sm mb-4">Generate sample callback data to test your callback handler.</p>
                
                <form method="POST">
                    <input type="hidden" name="test_type" value="callback_test">
                    <button type="submit" class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition">
                        <i class="fas fa-code mr-2"></i>Generate Callback
                    </button>
                </form>
                
                <?php if (isset($test_results['callback'])): ?>
                <div class="mt-4 p-3 bg-gray-800/50 rounded-lg">
                    <pre class="text-xs text-gray-300 overflow-auto max-h-40"><?php echo json_encode($test_results['callback'], JSON_PRETTY_PRINT); ?></pre>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Transactions -->
        <section class="glass-card rounded-xl p-6 mt-8">
            <h3 class="text-lg font-bold text-white mb-4">Recent Test Transactions</h3>
            
            <?php
            $stmt = $db->prepare("
                SELECT * FROM transactions 
                WHERE description LIKE '%test%' OR description LIKE '%Test%'
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $test_transactions = $stmt->fetchAll();
            ?>
            
            <?php if (!empty($test_transactions)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="text-left py-2 text-gray-400">ID</th>
                                <th class="text-left py-2 text-gray-400">Amount</th>
                                <th class="text-center py-2 text-gray-400">Status</th>
                                <th class="text-left py-2 text-gray-400">Receipt</th>
                                <th class="text-left py-2 text-gray-400">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($test_transactions as $txn): ?>
                            <tr class="border-b border-gray-800">
                                <td class="py-2 text-white"><?php echo $txn['id']; ?></td>
                                <td class="py-2 text-white"><?php echo formatMoney($txn['amount']); ?></td>
                                <td class="py-2 text-center">
                                    <span class="px-2 py-1 rounded text-xs <?php 
                                    echo match($txn['status']) {
                                        'completed' => 'bg-green-500/20 text-green-400',
                                        'pending' => 'bg-yellow-500/20 text-yellow-400',
                                        'failed' => 'bg-red-500/20 text-red-400',
                                        default => 'bg-gray-500/20 text-gray-400'
                                    };
                                    ?>">
                                        <?php echo ucfirst($txn['status']); ?>
                                    </span>
                                </td>
                                <td class="py-2 text-gray-300"><?php echo $txn['mpesa_receipt'] ?: 'N/A'; ?></td>
                                <td class="py-2 text-gray-400"><?php echo date('M j, H:i', strtotime($txn['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-400">No test transactions found.</p>
            <?php endif; ?>
        </section>

        <!-- Instructions -->
        <section class="glass-card rounded-xl p-6 mt-8">
            <h3 class="text-lg font-bold text-white mb-4">Testing Instructions</h3>
            
            <div class="space-y-4 text-gray-300">
                <div>
                    <h4 class="font-semibold text-white">1. Connection Test</h4>
                    <p class="text-sm">Verifies that your M-Pesa credentials are correct and can obtain an access token from Safaricom.</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-white">2. STK Push Test</h4>
                    <p class="text-sm">Sends an actual payment request to the provided phone number. Use a real number you have access to.</p>
                    <p class="text-xs text-yellow-400">⚠️ This will send a real payment prompt to the phone!</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-white">3. Callback Test</h4>
                    <p class="text-sm">Generates sample callback data. In production, this data comes from M-Pesa after payment completion.</p>
                </div>
                
                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mt-6">
                    <h4 class="font-semibold text-blue-400 mb-2">Sandbox Testing</h4>
                    <p class="text-sm">When using sandbox environment:</p>
                    <ul class="text-xs mt-2 space-y-1">
                        <li>• Use the provided test credentials</li>
                        <li>• Phone numbers should be in format 254XXXXXXXXX</li>
                        <li>• Payments won't actually deduct money</li>
                        <li>• Callbacks should still work normally</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Auto-refresh test results every 30 seconds
        let refreshInterval;
        
        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                // Check for pending transactions and refresh if needed
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        // Update only the recent transactions section
                        const parser = new DOMParser();
                        const newDoc = parser.parseFromString(html, 'text/html');
                        const newSection = newDoc.querySelector('.glass-card:nth-last-child(2)');
                        const currentSection = document.querySelector('.glass-card:nth-last-child(2)');
                        
                        if (newSection && currentSection) {
                            currentSection.innerHTML = newSection.innerHTML;
                        }
                    });
            }, 30000);
        }
        
        // Start auto-refresh when page loads
        document.addEventListener('DOMContentLoaded', startAutoRefresh);
    </script>
</body>
</html>