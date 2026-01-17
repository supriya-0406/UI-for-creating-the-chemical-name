<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['compound']) || empty(trim($_GET['compound']))) {
    echo json_encode(['error' => 'No chemical name provided']);
    exit;
}

$original_query = trim($_GET['compound']);

function apiCall($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'ChemicalSearch/4.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code == 200 && $res) ? $res : false;
}

// Step 1: Search for best matching title
$search_json = apiCall("https://en.wikipedia.org/w/api.php?action=query&format=json&list=search&srsearch=" . urlencode($original_query) . "&srlimit=1");
if (!$search_json) {
    echo json_encode(['error' => 'Failed to connect to Wikipedia']);
    exit;
}

$search_data = json_decode($search_json, true);

if (empty($search_data['query']['search'])) {
    // No results
    echo json_encode([
        'name' => $original_query,
        'title' => 'Not Found',
        'description_type' => 'Unknown',
        'thumbnail_url' => null,
        'content' => '<p>No Wikipedia page found for "<strong>' . htmlspecialchars($original_query) . '</strong>".</p>',
        'was_redirected' => false
    ]);
    exit;
}

$best_title = $search_data['query']['search'][0]['title'];
$used_name = $best_title;

// Step 2: Get full article HTML using parse (with correct formatversion)
$parse_html = apiCall("https://en.wikipedia.org/w/api.php?action=parse&page=" . urlencode($best_title) . "&format=json&formatversion=2&prop=text|displaytitle|properties");

if (!$parse_html) {
    echo json_encode([
        'name' => $original_query,
        'title' => $used_name,
        'content' => '<p>Failed to load full article content.</p>',
        'was_redirected' => true
    ]);
    exit;
}

$parse_data = json_decode($parse_html, true);

if (isset($parse_data['error'])) {
    echo json_encode([
        'name' => $original_query,
        'title' => $used_name,
        'content' => '<p>Wikipedia error: ' . htmlspecialchars($parse_data['error']['info']) . '</p>',
        'was_redirected' => true
    ]);
    exit;
}

// Extract the HTML content
$parse_result = $parse_data['parse'] ?? [];
$html = $parse_result['text'] ?? '<p>No content available.</p>';
$title = $parse_result['title'] ?? $used_name;
$displaytitle = $parse_result['displaytitle'] ?? $title;

// Extract description if available
$wikidesc = $parse_result['properties']['description'] ?? 'Informational content from Wikipedia';

// Sanitize HTML: Remove edit links, metadata, etc.
$html = preg_replace('/<span class="mw-editsection">.*?<\/span>/i', '', $html); // Remove [edit]
$html = preg_replace('/href="[^"]*"/i', '', $html); // Remove all hrefs
$html = preg_replace('/class="[^"]*"/i', '', $html); // Remove classes that break style
$html = preg_replace('/id="[^"]*"/i', '', $html);
$html = preg_replace('/data-[^ >]+/i', '', $html);
$html = preg_replace('/<script.*?<\/script>/is', '', $html);

// Optional: keep only content before "See also", "References", etc.
$sections_to_remove = ['See also', 'References', 'Further reading', 'External links'];
foreach ($sections_to_remove as $sec) {
    $pos = stripos($html, "<span class=\"mw-headline\" id=\"");
    if ($pos !== false) {
        $before = substr($html, 0, $pos);
        $html = $before . '<p><em>Note: Additional sections like references are hidden for clarity.</em></p>';
        break;
    }
}

// Return clean data
echo json_encode([
    'name' => $original_query,
    'title' => $displaytitle,
    'description_type' => $wikidesc,
    'content' => $html,
    'was_redirected' => true
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>