<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chemical Information from Wikipedia</title>
    <!-- W3.CSS -->
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafc;
        }
    </style>
</head>
<body class="w3-content" style="max-width: 800px; margin-top: 40px;">

    <!-- Header -->
    <div class="w3-center w3-margin-bottom">
        <h1 class="w3-text-blue">Chemical Information Viewer</h1>
    </div>

    <!-- Search + Note Container -->
    <div class="w3-container w3-padding">

        <!-- 🔍 Search Bar -->
        <div class="w3-row w3-margin-bottom">
            <div class="w3-col s10 m9 l10 w3-padding-right">
                <input type="text" id="chemicalInput"
                    class="w3-input w3-border w3-round-large"
                    placeholder="Enter chemical name (e.g. Benzene, Ethanol)">
            </div>
            <div class="w3-col s2 m3 l2 w3-padding-left">
                <button onclick="searchChemical()"
                    class="w3-button w3-blue w3-round-large w3-block w3-padding">
                    Search
                </button>
            </div>
        </div>

        <!-- 📝 Note Box (directly below, same width) -->
        <div class="w3-margin-top">
            <label class="w3-opacity w3-small">Your Notes</label>
            <textarea id="plainTextbox"
                class="w3-input w3-border w3-round-large w3-margin-top"
                placeholder="Add your notes here..."
                rows="5"
                style="resize: vertical;"></textarea>
        </div>

    </div>

    <!-- Right-Side Panel (Hidden by default) -->
    <div id="resultPanel" class="w3-sidebar w3-right w3-white w3-card-4 w3-animate-right" style="width: 580px; height: 100vh; position: fixed; top: 0; right: -580px; overflow-y: auto;">
        <div class="w3-display-container">
            <span onclick="closePanel()" class="w3-display-topright w3-button w3-xlarge w3-hover-red w3-padding-small" style="top: 8px; right: 8px;">×</span>
            <div id="resultContent" class="w3-container w3-padding-32">
                <div class="w3-center w3-text-gray w3-padding">
                    <p>Enter a chemical name and click <b>Search</b> to view full details.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function searchChemical() {
            const query = document.getElementById('chemicalInput').value.trim();
            if (!query) {
                alert("Please enter a chemical name.");
                return;
            }

            const content = document.getElementById('resultContent');
            content.innerHTML = '<div class="w3-center w3-text-gray"><p>Loading article...</p></div>';
            document.getElementById('resultPanel').style.right = "0";

            fetch('search.php?compound=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = `<div class="w3-panel w3-red w3-text-white w3-round w3-margin-top"><p>⚠️ ${data.error}</p></div>`;
                    } else {
                        const html = `
                            <h2 class="w3-text-blue">${data.title}</h2>
                            ${data.was_redirected ? 
                              `<p class="w3-text-red w3-small w3-margin-top">⚠️ Showing closest match for "${data.name}".</p>` : ''}
                            <div class="w3-margin-top">${data.content}</div>
                        `;
                        content.innerHTML = html;
                    }
                })
                .catch(err => {
                    content.innerHTML = `<div class="w3-panel w3-red w3-text-white w3-round w3-margin-top"><p>❌ Failed to load. Is search.php running?</p></div>`;
                });
        }

        function closePanel() {
            document.getElementById('resultPanel').style.right = "-580px";
        }

        // Enter key support
        document.getElementById('chemicalInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') searchChemical();
        });
    </script>

</body>
</html>