<?php
function pascco_savings_products(): array
{
    return [
        'Passbook Accounts' => [
            ['name' => 'Laboratory Cooperative Savings', 'description' => 'For members ages 0 to 17. Earns 1% interest per year.'],
            ['name' => 'Regular Savings', 'description' => 'For regular and associate members aged 18 and above. Earns 1% interest per year with a PHP 1,000 maintaining balance to earn interest.'],
        ],
        'Programmed Savings Deposits' => [
            ['name' => 'Paluwagan', 'description' => 'Save regularly according to the cooperative program.'],
            ['name' => 'Car Savings Deposit', 'description' => 'A goal-based savings account for a vehicle purchase.'],
            ['name' => 'House Savings Deposit', 'description' => 'A goal-based savings account for housing needs.'],
            ['name' => 'Education Savings Deposit', 'description' => 'A goal-based savings account for education expenses.'],
            ['name' => 'Wedding Savings Deposit', 'description' => 'A goal-based savings account for wedding expenses.'],
            ['name' => 'Travel Savings Deposit', 'description' => 'A goal-based savings account for travel plans.'],
            ['name' => 'Debut Savings Deposit', 'description' => 'A goal-based savings account for debut expenses.'],
        ],
        'Time Deposit' => [
            ['name' => 'Time Deposit', 'description' => 'Fixed-term deposit rates vary by placement amount.'],
        ],
    ];
}

function pascco_savings_product_names(): array
{
    $names = [];
    foreach (pascco_savings_products() as $products) {
        foreach ($products as $product) {
            $names[] = $product['name'];
        }
    }
    return $names;
}
