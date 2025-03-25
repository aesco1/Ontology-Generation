<?php
/**
 * Plugin Name: Ontology Generator
 * Description: WordPress plugin to generate ontologies using Llama 3.2
 * Version: 3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode to display the ontology gen. form
function ontology_generator_shortcode() {
    ob_start();
    ?>
    <div class="ontology-generator-container">
        <h2>Domain Ontology Generator</h2>
        <p>Enter a domain keyword to generate an ontology with Llama 3.2.</p>
        
        <form id="ontology-generator-form">
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
        </div>
    </div>

    <!--- CSS STYLES -->
    <style>
        .ontology-generator-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .submit-button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        .submit-button:hover {
            background: #005177;
        }
        .result-container {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
        }
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #0073aa;
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
        .error {
            color: #d63638;
            padding: 10px;
            background: #ffebe8;
            border-left: 4px solid #d63638;
        }
        h3 {
            color: #0073aa;
            margin-top: 0;
        }
        .relationships-container {
            margin: 20px 0;
        }
        .relationship-item {
            background: #f5f7fa;
            border: 1px solid #e1e4e8;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 10px;
            font-size: 16px;
            line-height: 1.5;
        }
        .entity {
            font-weight: bold;
            color: #0073aa;
        }
        .relation {
            font-style: italic;
            color: #333;
            margin: 0 10px;
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            $('#ontology-generator-form').submit(function(e) {
                e.preventDefault();
                
                $('#loading-indicator').show();
                $('#relationships-display').empty();
                
                var domain = $('#domain').val();
                
                // Make AJACK call to script
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'generate_ontology',
                        domain: domain,
                        nonce: '<?php echo wp_create_nonce('ontology_generator_nonce'); ?>'
                    },
                    // Expected response format:
                    // {success: true, data: {domain: ...}
                    // {success: false, data: "error msg"}
                    success: function(response) {
                        $('#loading-indicator').hide();
                        
                        if (response.success) {
                            displayRelationships(response.data);
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
                // Ensure domain exists and use fallback if needed
                var domainText = "Unknown Domain";
                if (typeof ontology === 'object' && ontology !== null && ontology.domain) {
                    domainText = String(ontology.domain);
                }
                
                var html = '<h3>Relationships for Domain: ' + domainText + '</h3>';
                
                // Check data structure
                if (!ontology || typeof ontology !== 'object' || !Array.isArray(ontology.relationships)) {
                    html += '<p class="error">Error: Invalid ontology data received. Please try again.</p>';
                    $('#relationships-display').html(html);
                    return;
                }
                
                // Maps and normalizes the relationship data
                // Fallbacks used for missing values
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
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('ontology_generator', 'ontology_generator_shortcode');

function ontology_generator_enqueue_scripts() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'ontology_generator_enqueue_scripts');


// AJAX callback to execute python script, and return response
function generate_ontology_callback() {
    check_ajax_referer('ontology_generator_nonce', 'nonce');
    
    $domain = sanitize_text_field($_POST['domain']);
    
    if (empty($domain)) {
        wp_send_json_error('Domain is required');
    }
    
    // Set up Python
    // Paths may differ based on server
    $python_path = 'python3'; 
    $script_path = plugin_dir_path(__FILE__) . 'ontology_generator.py';
    
    // Build and escape for securtiy purposes
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

// AJAX  handlers for logged-in/non-logged in users
add_action('wp_ajax_generate_ontology', 'generate_ontology_callback');
add_action('wp_ajax_nopriv_generate_ontology', 'generate_ontology_callback');


// ====== ADMIN INTERFACE ====================

// Add admin menu
function ontology_generator_admin_menu() {
    add_menu_page(
        'Ontology Generator',               // Page title
        'Ontology Generator',               // Menu title
        'manage_options',                   //Capability
        'ontology-generator',               // Menu slug
        'ontology_generator_admin_page',    //Callback function
        'dashicons-networking',             //Icon
        30                                  // Position
    );
}
add_action('admin_menu', 'ontology_generator_admin_menu');

// Admin page content
function ontology_generator_admin_page() {
    ?>
    <div class="wrap">
        <h1>Ontology Generator</h1>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2>Instructions</h2>
            <p>Use the shortcode <code>[ontology_generator]</code> on any page or post to display the ontology generator form.</p>
            <p>Users can enter a domain, and the plugin will generate relationship mappings using Llama 3.2.</p>
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
            <h2>Test Generator</h2>
            <p>You can test the relationship generator below:</p>
            <?php echo do_shortcode('[ontology_generator]'); ?>
        </div>
    </div>
    <?php
}
