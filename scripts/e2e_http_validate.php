<?php

declare(strict_types=1);

$baseUrl = rtrim(getenv('BD_BASE_URL') ?: 'https://bantudelice.cg', '/');

$accounts = [
    'admin' => [
        'email' => getenv('BD_ADMIN_EMAIL') ?: '',
        'password' => getenv('BD_ADMIN_PASSWORD') ?: '',
        'home' => '/admin',
        'checks' => [
            ['/admin', 'Tableau de bord'],
            ['/admin/cms/contents', 'Contenus'],
            ['/admin/restaurant_payout', 'Paiements restaurants'],
        ],
    ],
    'restaurant' => [
        'email' => getenv('BD_RESTAURANT_EMAIL') ?: '',
        'password' => getenv('BD_RESTAURANT_PASSWORD') ?: '',
        'home' => '/restaurant',
        'checks' => [
            ['/restaurant', 'Tableau de bord'],
            ['/restaurant/menu', 'Menu'],
            ['/restaurant/kitchen', 'kitchen'],
        ],
    ],
    'driver' => [
        'email' => getenv('BD_DRIVER_EMAIL') ?: '',
        'password' => getenv('BD_DRIVER_PASSWORD') ?: '',
        'home' => '/driver/deliveries',
        'checks' => [
            ['/driver/deliveries', 'Mes Livraisons'],
        ],
    ],
];

function request_with_session(string $method, string $url, array $payload, string $cookieFile, ?string $userAgent = null): array
{
    $ch = curl_init($url);
    $headers = ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 45,
    ]);

    if ($userAgent) {
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    }

    if ($payload) {
        $encoded = http_build_query($payload);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $raw = curl_exec($ch);
    if ($raw === false) {
        throw new RuntimeException('cURL failure for ' . $url . ': ' . curl_error($ch));
    }

    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headersText = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    curl_close($ch);

    $location = null;
    foreach (explode("\n", $headersText) as $line) {
        if (stripos($line, 'Location:') === 0) {
            $location = trim(substr($line, 9));
        }
    }

    return [
        'status' => $status,
        'headers' => $headersText,
        'body' => $body,
        'location' => $location,
    ];
}

function absolute_url(string $baseUrl, ?string $location): ?string
{
    if (!$location) {
        return null;
    }

    if (preg_match('#^https?://#i', $location)) {
        return $location;
    }

    return $baseUrl . '/' . ltrim($location, '/');
}

function extract_csrf(string $html): string
{
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    throw new RuntimeException('Unable to extract CSRF token from login page.');
}

function assert_contains(string $haystack, string $needle, string $label): void
{
    if (stripos($haystack, $needle) === false) {
        throw new RuntimeException("Missing expected marker '{$needle}' on {$label}.");
    }
}

$report = [
    'base_url' => $baseUrl,
    'results' => [],
];

foreach ($accounts as $role => $config) {
    if (!$config['email'] || !$config['password']) {
        $report['results'][$role] = ['status' => 'skipped', 'reason' => 'missing_credentials'];
        continue;
    }

    $cookieFile = tempnam(sys_get_temp_dir(), 'bd-e2e-cookie-');
    try {
        $loginPage = request_with_session('GET', $baseUrl . '/login', [], $cookieFile);
        $token = extract_csrf($loginPage['body']);

        $loginResponse = request_with_session('POST', $baseUrl . '/login', [
            '_token' => $token,
            'email' => $config['email'],
            'password' => $config['password'],
        ], $cookieFile);

        if (!in_array($loginResponse['status'], [301, 302, 303], true)) {
            throw new RuntimeException("Unexpected login status {$loginResponse['status']} for {$role}.");
        }

        $redirect = absolute_url($baseUrl, $loginResponse['location']);
        if ($redirect === null || stripos($redirect, $config['home']) === false) {
            throw new RuntimeException("Unexpected login redirect for {$role}: " . ($redirect ?: 'none'));
        }

        $checks = [];

        foreach ($config['checks'] as [$path, $marker]) {
            $desktop = request_with_session('GET', $baseUrl . $path, [], $cookieFile);
            if ($desktop['status'] !== 200) {
                throw new RuntimeException("Unexpected status {$desktop['status']} for {$role} page {$path}.");
            }
            assert_contains($desktop['body'], $marker, "{$role}:{$path}:desktop");

            $mobile = request_with_session('GET', $baseUrl . $path, [], $cookieFile, 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
            if ($mobile['status'] !== 200) {
                throw new RuntimeException("Unexpected mobile status {$mobile['status']} for {$role} page {$path}.");
            }
            assert_contains($mobile['body'], $marker, "{$role}:{$path}:mobile");

            $checks[] = [
                'path' => $path,
                'marker' => $marker,
                'desktop_status' => $desktop['status'],
                'mobile_status' => $mobile['status'],
            ];
        }

        $report['results'][$role] = [
            'status' => 'ok',
            'redirect' => $redirect,
            'checks' => $checks,
        ];
    } catch (Throwable $e) {
        $report['results'][$role] = [
            'status' => 'failed',
            'message' => $e->getMessage(),
        ];
    } finally {
        if (is_file($cookieFile)) {
            @unlink($cookieFile);
        }
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$hasFailure = false;
foreach ($report['results'] as $result) {
    if (($result['status'] ?? null) === 'failed') {
        $hasFailure = true;
        break;
    }
}

exit($hasFailure ? 1 : 0);
