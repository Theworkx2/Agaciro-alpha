<?php
require_once 'config/database.php';

// Function to get member ID by name
function getMemberId($conn, $name) {
    $query = "SELECT id FROM members WHERE name = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['id'];
}

// Function to extract fee from description
function extractFee($description) {
    if (preg_match('/(?:fees?|charge)\s*-*\s*(\d+)(?:\s*RWF)?/i', $description, $matches)) {
        return intval($matches[1]);
    }
    return 0;
}

// Historical transactions data
$transactions = [
    // January 2025 (Entries 1-13)
    1 => ['2025-01-25 21:11:00', 'TWUBAHIMANA J.Nepo Muscenee', 'deposit', 500, 'on time'],
    2 => ['2025-01-25 21:13:00', 'BYIRINGIRO David', 'deposit', 500, 'on time'],
    3 => ['2025-01-25 21:14:00', 'RIRASIRABOSE Aime Nicole', 'deposit', 1500, 'on time'],
    4 => ['2025-01-25 21:16:00', 'UWIRAGIYE Ventantier', 'deposit', 2000, 'on time'],
    5 => ['2025-01-25 21:16:00', 'RUGWIRO Elisa', 'deposit', 2000, 'on time'],
    6 => ['2025-01-29 20:40:00', 'BYIRINGIRO David', 'deposit', 18000, 'Quick transaction'],
    7 => ['2025-01-29 22:28:00', 'RIRASIRABOSE Aime Nicole', 'withdrawal', 1500, 'Aime ashaka amafaranga ya ticket ya meeting'],
    8 => ['2025-01-29 22:29:00', 'UWIRAGIYE Ventantier', 'withdrawal', 1500, 'Aime ashaka amafaranga ya ticket ya meeting'],
    9 => ['2025-01-30 12:16:00', 'RIRASIRABOSE Aime Nicole', 'deposit', 1500, 'Mama aramwishyuriye'],
    10 => ['2025-01-30 12:17:00', 'UWIRAGIYE Ventantier', 'deposit', 1500, 'on time'],
    11 => ['2025-01-30 18:51:00', 'RUGWIRO Elisa', 'deposit', 1000, 'on time'],
    12 => ['2025-01-30 20:49:00', 'TWUBAHIMANA J.Nepo Muscenee', 'deposit', 1000, 'on time'],
    13 => ['2025-01-31 14:59:00', 'BYIRINGIRO David', 'withdrawal', 5000, 'Ayakuye kuri 18k'],

    // February 2025 (Entries 14-83)
    14 => ['2025-02-01 13:59:00', 'BYIRINGIRO David', 'deposit', 7000, 'on time'],
    15 => ['2025-02-01 17:37:00', 'BYIRINGIRO David', 'withdrawal', 8000, 'for job purpose'],
    16 => ['2025-02-01 18:18:00', 'TWUBAHIMANA J.Nepo Muscenee', 'withdrawal', 1000, 'ticket down tow scchool'],
    17 => ['2025-02-01 19:03:00', 'BYIRINGIRO David', 'withdrawal', 1500, 'joby purpose'],
    18 => ['2025-02-01 23:09:00', 'BYIRINGIRO David', 'deposit', 4000, 'on time'],
    19 => ['2025-02-02 14:54:00', 'BYIRINGIRO David', 'deposit', 12000, 'on time'],
    20 => ['2025-02-03 18:37:00', 'BYIRINGIRO David', 'withdrawal', 2000, 'voice call purpose'],
    21 => ['2025-02-04 14:30:00', 'BYIRINGIRO David', 'deposit', 2500, 'on time and withdrawed 500frw'],
    22 => ['2025-02-05 10:44:00', 'BYIRINGIRO David', 'withdrawal', 3000, 'joby purpose'],
    23 => ['2025-02-05 14:27:00', 'RUGWIRO Elisa', 'withdrawal', 710, 'buying socket(square)'],
    24 => ['2025-02-05 17:32:00', 'BYIRINGIRO David', 'deposit', 15000, 'joby purpose'],
    25 => ['2025-02-05 17:37:00', 'BYIRINGIRO David', 'deposit', 5000, 'joby purpose'],
    26 => ['2025-02-05 17:46:00', 'UWIRAGIYE Ventantier', 'deposit', 10000, 'Deal done'],
    27 => ['2025-02-06 09:02:00', 'BYIRINGIRO David', 'deposit', 12800, 'Job purpose'],
    28 => ['2025-02-06 10:43:00', 'RUGWIRO Elisa', 'withdrawal', 100, 'kugura ama inite'],
    29 => ['2025-02-06 11:01:00', 'UWIRAGIYE Ventantier', 'withdrawal', 100, 'kugura ama inite'],
    30 => ['2025-02-06 11:07:00', 'UWIRAGIYE Ventantier', 'withdrawal', 5000, 'Kohereza Jean Marie Vianney'],
    31 => ['2025-02-06 13:07:00', 'BYIRINGIRO David', 'withdrawal', 2000, 'job purpose'],
    32 => ['2025-02-06 19:36:00', 'RUGWIRO Elisa', 'withdrawal', 500, 'ayo gukata kuri momo'],
    33 => ['2025-02-06 19:36:00', 'UWIRAGIYE Ventantier', 'withdrawal', 350, 'Isabune'],
    34 => ['2025-02-06 20:54:00', 'BYIRINGIRO David', 'withdrawal', 1000, 'joby purpose'],
    35 => ['2025-02-06 21:20:00', 'UWIRAGIYE Ventantier', 'deposit', 5000, 'to be paid to morror on agent 07/02/2025'],
    36 => ['2025-02-06 21:35:00', 'BYIRINGIRO David', 'deposit', 2000, 'to be paid to morror on agent'],
    37 => ['2025-02-06 21:44:00', 'TWUBAHIMANA J.Nepo Muscenee', 'deposit', 10500, 'personal saving'],
    38 => ['2025-02-06 22:13:00', 'BYIRINGIRO David', 'deposit', 12400, 'job purpose'],
    39 => ['2025-02-07 09:02:00', 'BYIRINGIRO David', 'withdrawal', 500, 'breakfast'],
    40 => ['2025-02-07 09:43:00', 'BYIRINGIRO David', 'withdrawal', 1000, 'other stuffs'],
    41 => ['2025-02-07 11:47:00', 'BYIRINGIRO David', 'withdrawal', 15000, 'joby purpose'],
    42 => ['2025-02-08 10:13:00', 'TWUBAHIMANA J.Nepo Muscenee', 'withdrawal', 3112, 'Tuyubahe yishyuye amazi'],
    43 => ['2025-02-08 10:34:00', 'TWUBAHIMANA J.Nepo Muscenee', 'withdrawal', 2700, 'personal reason'],
    44 => ['2025-02-08 12:46:00', 'TWUBAHIMANA J.Nepo Muscenee', 'withdrawal', 100, 'withdrawal fees of 2,700 RWF'],
    45 => ['2025-02-08 15:03:00', 'UWIRAGIYE Ventantier', 'withdrawal', 100, 'ama inite'],
    46 => ['2025-02-08 16:15:00', 'BYIRINGIRO David', 'withdrawal', 6100, 'Job purpose _ widrawal charge fees -100'],
    47 => ['2025-02-08 17:20:00', 'BYIRINGIRO David', 'withdrawal', 6100, 'Job purpose _ widrawal charge fees -100'],
    48 => ['2025-02-09 04:52:00', 'UWIRAGIYE Ventantier', 'withdrawal', 5350, 'withdrawal fees -100'],
    49 => ['2025-02-09 08:26:00', 'TWUBAHIMANA J.Nepo Muscenee', 'withdrawal', 2500, 'Sunday ticket'],
    50 => ['2025-02-09 13:54:00', 'TWUBAHIMANA J.Nepo Muscenee', 'withdrawal', 1700, ' tuyubahe baptise'],
    51 => ['2025-02-09 14:04:00', 'UWIRAGIYE Ventantier', 'withdrawal', 400, 'guhemba tuyubahe baptise'],
    52 => ['2025-02-09 14:11:00', 'UWIRAGIYE Ventantier', 'withdrawal', 2700, 'guhemba tuyubahe baptise'],
    53 => ['2025-02-09 14:16:00', 'UWIRAGIYE Ventantier', 'withdrawal', 600, 'guhemba tuyubahe baptise'],
    54 => ['2025-02-09 14:23:00', 'UWIRAGIYE Ventantier', 'withdrawal', 2000, 'guhemba tuyubahe baptise'],
    55 => ['2025-02-09 15:35:00', 'BYIRINGIRO David', 'withdrawal', 1020, 'urgent'],
    56 => ['2025-02-09 21:33:00', 'UWIRAGIYE Ventantier', 'withdrawal', 100, 'ama inite'],
    57 => ['2025-02-10 11:04:00', 'BYIRINGIRO David', 'withdrawal', 2100, 'job purpose'],
    58 => ['2025-02-10 14:38:00', 'BYIRINGIRO David', 'deposit', 7500, 'joby purpose'],
    59 => ['2025-02-10 15:39:00', 'BYIRINGIRO David', 'withdrawal', 7100, 'joby purpose'],
    60 => ['2025-02-10 17:36:00', 'TWUBAHIMANA J.Nepo Muscenee', 'withdrawal', 870, 'ticket down town'],
    61 => ['2025-02-10 20:42:00', 'RUGWIRO Elisa', 'withdrawal', 3100, 'Buy Fast Charge black (other remaining shall be payed)'],
    62 => ['2025-02-10 23:05:00', 'BYIRINGIRO David', 'deposit', 200000, 'joby purpose'],
    63 => ['2025-02-11 09:01:00', 'BYIRINGIRO David', 'withdrawal', 2100, 'Kwiyogoshesha'],
    64 => ['2025-02-11 10:15:00', 'UWIRAGIYE Ventantier', 'deposit', 19722, 'on time'],
    65 => ['2025-02-11 13:49:00', 'BYIRINGIRO David', 'withdrawal', 3100, 'Appoitment kwamuganga'],
    66 => ['2025-02-11 16:06:00', 'BYIRINGIRO David', 'withdrawal', 5100, 'Appoitment Imasaka'],
    67 => ['2025-02-11 17:05:00', 'UWIRAGIYE Ventantier', 'withdrawal', 1100, 'tuyubahe kicket to downtown school'],
    68 => ['2025-02-11 19:22:00', 'UWIRAGIYE Ventantier', 'withdrawal', 300, 'ama inite yokuzana 4ne nshya ya mama (from Papa Luxxen)'],
    69 => ['2025-02-12 11:06:00', 'UWIRAGIYE Ventantier', 'withdrawal', 500, 'pack yicyumweru'],
    70 => ['2025-02-12 14:19:00', 'BYIRINGIRO David', 'withdrawal', 2100, 'joby purpose'],
    71 => ['2025-02-12 15:59:00', 'BYIRINGIRO David', 'withdrawal', 520, 'other reasons'],
    72 => ['2025-02-12 19:52:00', 'UWIRAGIYE Ventantier', 'withdrawal', 2100, 'Kohereza Aime amafaranga'],
    73 => ['2025-02-13 11:37:00', 'BYIRINGIRO David', 'withdrawal', 201500, 'Job purpose _charge fees -1,500RWF'],
    74 => ['2025-02-13 12:24:00', 'UWIRAGIYE Ventantier', 'withdrawal', 5100, 'Kohereza mucekuru mucyaro -charge fees -100'],
    75 => ['2025-02-14 12:21:00', 'BYIRINGIRO David', 'withdrawal', 1020, 'other reasons'],
    76 => ['2025-02-14 15:31:00', 'UWIRAGIYE Ventantier', 'withdrawal', 10100, 'Gutwerera mama Enock'],
    77 => ['2025-02-14 17:13:00', 'BYIRINGIRO David', 'withdrawal', 3200, 'other reasons-fees -100'],
    78 => ['2025-02-15 07:57:00', 'BYIRINGIRO David', 'withdrawal', 15250, 'Job purpose'],
    79 => ['2025-02-16 14:43:00', 'RIRASIRABOSE Aime Nicole', 'withdrawal', 1400, 'Kohereza JMV -charge 100'],
    80 => ['2025-02-16 14:42:00', 'RIRASIRABOSE Aime Nicole', 'deposit', 1500, 'Deposit for withdrawal'],
    81 => ['2025-02-16 19:49:00', 'RUGWIRO Elisa', 'deposit', 1410, 'payback of black charger'],
    82 => ['2025-02-16 19:55:00', 'RUGWIRO Elisa', 'deposit', 590, 'contribution'],
    83 => ['2025-02-16 22:03:00', 'BYIRINGIRO David', 'withdrawal', 1020, 'other stuffs'],
    84 => ['2025-02-17 10:28:00', 'BYIRINGIRO David', 'withdrawal', 2470, 'joby purpose']
];

// Clear existing transactions if needed
mysqli_query($conn, "TRUNCATE TABLE transactions");

// Insert transactions
$insert_query = "INSERT INTO transactions (member_id, type, amount, fees, reason, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insert_query);

foreach ($transactions as $transaction) {
    $member_id = getMemberId($conn, $transaction[1]);
    $type = $transaction[2];
    $amount = $transaction[3];
    $reason = $transaction[4];
    $date = $transaction[0];
    $fees = extractFee($reason);

    mysqli_stmt_bind_param($stmt, "issdss", $member_id, $type, $amount, $fees, $reason, $date);
    
    if (!mysqli_stmt_execute($stmt)) {
        echo "Error importing transaction: " . mysqli_error($conn) . "\n";
        echo "Transaction details: " . print_r($transaction, true) . "\n";
    }
}

mysqli_stmt_close($stmt);
echo "Import completed successfully!";
?> 