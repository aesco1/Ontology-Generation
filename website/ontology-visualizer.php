<?php
/**
 * Plugin Name: Ontology Generator (Visual)
 * Description: WordPress plugin to generate ontologies using Llama 3.2 with Mermaid visualization
 * Version: 1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode to display the ontology gen. form
function ontology_visualizer_shortcode() {
    ob_start();
    ?>
    <div class="ontology-generator-container">
        <h2>Domain Ontology Generator</h2>
        <p>Enter a domain keyword to generate an ontology with Llama 3.2.</p>
        
        <form id="ontology-visualizer-form">
            <div class="form-group">
                <label for="domain">Domain:</label>
                <input type="text" id="domain" name="domain" placeholder="Enter a domain (e.g., education, healthcare, pets)" required>
            </div>
            
            <button type="submit" class="submit-button">Generate Relationships</button>
        </form>
        
        <div id="ontology-result" class="result-container">
            <div id="loading-indicator" style="display: none;">
                <p>Generating ontology relationships... This may take a minute.</p>
                <div class="loader"></div>
            </div>
            <div id="relationships-display"></div>
            
            <!-- Visualization container -->
            <div id="visualization-container" style="display: none;">
                <h3 class="visualization-header">Visual Representation</h3>
                <div id="mermaid-diagram" class="mermaid-container"></div>
            </div>
        </div>
    </div>

    <!--- CSS STYLES -->
    <style>
        /* UTD Brand Colors */
        :root {
            --utd-green: #154734;
            --utd-orange: #E87500;
            --utd-white: #fff;
            --ut-black: #333333;
            --silverleaf: #5fe0b7;
            --web-orange: #c95100;
        }
        
        /* Base Typography */
        .ontology-generator-container {
            font-family: din-2014, sans-serif;
            font-weight: 400;
            font-size: 1.125rem;
            line-height: 1.6;
            color: var(--ut-black);
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: var(--utd-white);
            border-radius: 5px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        
        /* Headings */
        .ontology-generator-container h2 {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--utd-orange);
            font-size: 2.5rem;
            font-weight: 300;
            letter-spacing: -0.83px;
        }
        
        .ontology-generator-container p {
            margin-top: 0;
            margin-bottom: 20px;
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 15px;
            box-sizing: border-box;
            width: 100%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: var(--utd-green);
        }
        
        .ontology-generator-container input[type="text"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            color: var(--ut-black);
            border: 2px solid var(--utd-green);
            background-color: transparent;
            outline: none;
            font-family: inherit;
            box-sizing: border-box;
        }
        
        /* UTD Button */
        .submit-button {
            width: 100%;
            padding: 5px 26px;
            font-size: 1.3125rem;
            font-weight: 700;
            color: var(--ut-black);
            background-color: var(--utd-white);
            text-transform: uppercase;
            text-align: center;
            border-width: 2px;
            border-style: solid;
            border-radius: 0;
            border-image: linear-gradient(to right, #154734 0%, #e87500 100%);
            border-image-slice: 1;
            transition: 0.3s;
            cursor: pointer;
            font-family: inherit;
            box-sizing: border-box;
        }
        
        .submit-button:hover,
        .submit-button:focus {
            color: var(--utd-white);
            background-color: var(--utd-green);
            text-decoration: none;
        }
        
        /* Results area */
        .result-container {
            margin-top: 30px;
            padding: 20px;
            background: var(--utd-white);
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            box-sizing: border-box;
        }
        
        .domain-header, .visualization-header {
            color: var(--utd-green);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        /* Relationships container */
        .relationships-container {
            margin: 20px 0;
        }
        
        /* Individual relationship items */
        .relationship-item {
            background: #f5f7fa;
            border: 1px solid #e1e4e8;
            border-left: 4px solid var(--utd-orange);
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 10px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .entity {
            font-weight: bold;
            color: var(--utd-orange);
        }
        
        .relation {
            font-style: italic;
            color: var(--utd-green);
            margin: 0 10px;
        }
        
        /* Mermaid container */
        .mermaid-container {
            width: 100%;
            overflow: auto;
            background-color: #24292e; /* Dark background like in the image */
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        /* Custom styles for mermaid diagram */
        .mermaid-container .node rect {
            fill: var(--utd-orange);
            stroke: var(--utd-green);
            stroke-width: 2px;
        }
        
        .mermaid-container .edgeLabel {
            background-color: #24292e;
            color: white;
        }
        
        /* Loader */
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--utd-orange);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Error message */
        .error {
            color: #d63638;
            padding: 10px;
            background: #ffebe8;
            border-left: 4px solid #d63638;
        }
        
        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            .loader {
                animation: none;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .ontology-generator-container h2 {
                font-size: 2.1875rem;
                letter-spacing: -0.72px;
            }
        }
        
        /* Toggle button */
        .view-toggle {
            display: flex;
            margin: 20px 0;
            gap: 10px;
        }
        
        .toggle-btn {
            padding: 6px 12px;
            background-color: #f5f7fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .toggle-btn.active {
            background-color: var(--utd-green);
            color: white;
            border-color: var(--utd-green);
        }
    </style>

    <!-- Load Mermaid library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mermaid/10.2.3/mermaid.min.js"></script>

    <script>
        jQuery(document).ready(function($) {
            // Initialize Mermaid
            mermaid.initialize({
                startOnLoad: true,
                theme: 'dark',
                securityLevel: 'loose',
                flowchart: {
                    useMaxWidth: true,
                    htmlLabels: true,
                    curve: 'basis'
                }
            });
            
            $('#ontology-visualizer-form').submit(function(e) {
                e.preventDefault();
                
                $('#loading-indicator').show();
                $('#relationships-display').empty();
                $('#visualization-container').hide();
                
                var domain = $('#domain').val();
                
                // Make AJAX call to script
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'generate_ontology_visualizer',
                        domain: domain,
                        nonce: '<?php echo wp_create_nonce('ontology_visualizer_nonce'); ?>'
                    },

                    success: function(response) {
                        $('#loading-indicator').hide();
                        
                        if (response.success) {
                            displayRelationships(response.data);
                            generateMermaidDiagram(response.data);
                            $('#visualization-container').show();
                        } else {
                            $('#relationships-display').html('<p class="error">Error: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        $('#loading-indicator').hide();
                        $('#relationships-display').html('<p class="error">Error connecting to the server. Please make sure Ollama is running.</p>');
                    }
                });
            });
            
            function displayRelationships(ontology) {
                // Data validation
                var domainText = "Unknown Domain";
                if (typeof ontology === 'object' && ontology !== null && ontology.domain) {
                    domainText = String(ontology.domain);
                }
                
                var html = '<h3 class="domain-header">Relationships for Domain: ' + domainText + '</h3>';
                
                // Add toggle buttons for list/visual views
                html += '<div class="view-toggle">';
                html += '<button class="toggle-btn active" id="list-view-btn">List View</button>';
                html += '<button class="toggle-btn" id="visual-view-btn">Visual View</button>';
                html += '</div>';
                
                // Check data structure
                if (!ontology || typeof ontology !== 'object' || !Array.isArray(ontology.relationships)) {
                    html += '<p class="error">Error: Invalid ontology data received. Please try again.</p>';
                    $('#relationships-display').html(html);
                    return;
                }
                
                // Map and normalize the rel. data
                var relationships = ontology.relationships.map(function(rel) {
                    return {
                        from: rel.from || "Unknown Source",
                        relationship: rel.relationship || "is related to",
                        to: rel.to || "Unknown Target"
                    };
                });
                
                // Display relationships
                if (relationships.length > 0) {
                    html += '<div class="relationships-container">';
                    
                    relationships.forEach(function(rel) {
                        html += '<div class="relationship-item">';
                        html += '<span class="entity">' + rel.from + '</span>';
                        html += '<span class="relation">→ ' + rel.relationship + ' →</span>';
                        html += '<span class="entity">' + rel.to + '</span>';
                        html += '</div>';
                    });
                    
                    html += '</div>';
                } else {
                    html += '<p>No relationships found for this domain. Try another domain or more specific term.</p>';
                }
                
                $('#relationships-display').html(html);
                
                // Set up toggle buttons
                $('#list-view-btn').click(function() {
                    $(this).addClass('active');
                    $('#visual-view-btn').removeClass('active');
                    $('.relationships-container').show();
                    $('#visualization-container').hide();
                });
                
                $('#visual-view-btn').click(function() {
                    $(this).addClass('active');
                    $('#list-view-btn').removeClass('active');
                    $('.relationships-container').hide();
                    $('#visualization-container').show();
                });
            }
            
            function generateMermaidDiagram(ontology) {
                if (!ontology || !Array.isArray(ontology.relationships) || ontology.relationships.length === 0) {
                    return;
                }
                
                // Create a mermaid flowchart definition
                var mermaidCode = 'flowchart LR\n';
                
                // Define node styles - using different colors for different node types
                var nodeStyles = {};
                var colorClasses = [
                    'style $id fill:#154734,stroke:#154734,color:white,stroke-width:1px',
                    'style $id fill:#E87500,stroke:#E87500,color:white,stroke-width:1px',
                    'style $id fill:#5fe0b7,stroke:#5fe0b7,color:white,stroke-width:1px',
                    'style $id fill:#c95100,stroke:#c95100,color:white,stroke-width:1px',
                    'style $id fill:#6a5acd,stroke:#6a5acd,color:white,stroke-width:1px',
                    'style $id fill:#2e8b57,stroke:#2e8b57,color:white,stroke-width:1px'
                ];
                
                // Collect all unique entity names
                var entities = new Set();
                ontology.relationships.forEach(function(rel) {
                    if (rel.from) entities.add(rel.from);
                    if (rel.to) entities.add(rel.to);
                });
                
                // Define node IDs and create node definitions
                var nodeIds = {};
                var nodeCounter = 1;
                
                entities.forEach(function(entity) {
                    // Create a safe ID for the node
                    var safeId = 'node' + nodeCounter++
                    nodeIds[entity] = safeId;
                    
                    // Create node definition with a rectangle shape and the entity name
                    mermaidCode += '    ' + safeId + '["' + entity + '"]\n';
                    
                    // Assign a style class based on a simple hash of the entity name
                    var colorIndex = Math.abs(entity.split('').reduce((a, b) => {
                        return a + b.charCodeAt(0);
                    }, 0)) % colorClasses.length;
                    
                    // Add style for this node
                    nodeStyles[safeId] = colorClasses[colorIndex].replace('$id', safeId);
                });
                
                // Define connections between nodes
                ontology.relationships.forEach(function(rel, index) {
                    if (rel.from && rel.to && nodeIds[rel.from] && nodeIds[rel.to]) {
                        var fromId = nodeIds[rel.from];
                        var toId = nodeIds[rel.to];
                        var relationshipText = rel.relationship || "is related to";
                        
                        // Create edge definition with the relationship as label
                        mermaidCode += '    ' + fromId + ' -->|' + relationshipText + '| ' + toId + '\n';
                    }
                });
                
                // Add node styles
                Object.values(nodeStyles).forEach(function(style) {
                    mermaidCode += '    ' + style + '\n';
                });
                
                // Clear previous diagram
                $('#mermaid-diagram').empty();
                
                // Create pre element with mermaid code
                var mermaidDiv = $('<div class="mermaid"></div>').text(mermaidCode);
                $('#mermaid-diagram').append(mermaidDiv);
                
                // Render the diagram
                mermaid.init(undefined, $('.mermaid'));
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('ontology_visualizer', 'ontology_visualizer_shortcode');

function ontology_visualizer_enqueue_scripts() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'ontology_visualizer_enqueue_scripts');


// AJAX callback to execute python script, and return response
function generate_ontology_visualizer_callback() {
    check_ajax_referer('ontology_visualizer_nonce', 'nonce');
    
    $domain = sanitize_text_field($_POST['domain']);
    
    if (empty($domain)) {
        wp_send_json_error('Domain is required');
    }
    
    // Set up Python
    // Paths may differ based on server
    $python_path = 'python3'; 
    $script_path = plugin_dir_path(__FILE__) . 'ontology_generator.py';
    
    // Build and escape for security purposes
    $command = escapeshellcmd($python_path . ' ' . $script_path . ' ' . escapeshellarg($domain));
    
    $output = shell_exec($command);
    
    // ERROR generating ontology
    if (!$output) {
        wp_send_json_error('Failed to generate ontology. Please ensure Python and Ollama are properly installed.');
        return;
    }
    
    // Try to parse JSON directly
    $ontology = json_decode($output, true);
    
    if ($ontology) {
        // ERROR
        if (isset($ontology['error'])) {
            wp_send_json_error('Error generating ontology: ' . $ontology['error']);
            return;
        }
        wp_send_json_success($ontology);
    } else {
        // Try to extract JSON from the output if it includes other irrelevant text
        $json_start = strpos($output, '{');
        $json_end = strrpos($output, '}');
        
        if ($json_start !== false && $json_end !== false && $json_end > $json_start) {
            $json_str = substr($output, $json_start, $json_end - $json_start + 1);
            $ontology = json_decode($json_str, true);
            
            if ($ontology) {
                wp_send_json_success($ontology);
                return;
            }
        }
        
        wp_send_json_error('Failed to parse output. Please check server configuration.');
    }
}

// AJAX handlers for logged-in/non-logged in users
add_action('wp_ajax_generate_ontology_visualizer', 'generate_ontology_visualizer_callback');
add_action('wp_ajax_nopriv_generate_ontology_visualizer', 'generate_ontology_visualizer_callback');


// ====== ADMIN INTERFACE ====================

// Add admin menu
function ontology_visualizer_admin_menu() {
    add_menu_page(
        'Ontology Visualizer',             // Page title
        'Ontology Visualizer',             // Menu title
        'manage_options',                  // Capability
        'ontology-visualizer',             // Menu slug
        'ontology_visualizer_admin_page',  // Callback function
        'dashicons-chart-area',            // Icon (different from original)
        31                                 // Position (different from original)
    );
}
add_action('admin_menu', 'ontology_visualizer_admin_menu');

// Admin page content
function ontology_visualizer_admin_page() {
    ?>
    <div class="wrap">
        <h1>Ontology Visualizer</h1>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2>Instructions</h2>
            <p>Use the shortcode <code>[ontology_visualizer]</code> on any page or post to display the ontology visualizer form.</p>
            <p>Users can enter a domain, and the plugin will generate relationship mappings using Llama 3.2, displayed both as a list and visual diagram.</p>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2>Requirements</h2>
            <p>This plugin requires:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>Python 3 installed on your server</li>
                <li>Ollama running with Llama 3.2 model available</li>
                <li>The requests Python package (<code>pip install requests</code>)</li>
            </ul>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2>UTD Theme</h2>
            <p>This plugin uses the University of Texas at Dallas (UTD) Oberon theme color scheme.</p>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2>Test Generator</h2>
            <p>You can test the relationship generator below:</p>
            <?php echo do_shortcode('[ontology_visualizer]'); ?>
        </div>
    </div>
    <?php
}
