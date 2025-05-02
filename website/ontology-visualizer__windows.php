<?php
/**
 * Plugin Name: Ontology Generator (Visual)
 * Description: WordPress plugin to generate ontologies using advanced LLMs with Mermaid visualization
 * Version: 4.2
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
        <div class="content-wrapper">
            <h2>Domain Ontology Generator</h2>
            <p class="intro-text">Enter a domain keyword to generate an ontology with advanced relationship modeling.</p>
            
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
                    <div class="diagram-instructions">Tip: You can zoom with the controls above and drag to pan when zoomed in.</div>
                </div>
            </div>
        </div>
    </div>

    <!--- CSS STYLES -->
    <style>
        /* UTD Brand Colors */
        :root {
            --utd-green: #154734;
            --utd-green-light: #2a8c68;
            --utd-orange: #E87500;
            --utd-orange-light: #ff9833;
            --utd-white: #fff;
            --ut-black: #333333;
            --silverleaf: #5fe0b7;
            --web-orange: #c95100;
            --light-gray: #f8f9fa;
            --border-color: #e0e0e0;
            
            /* Relationship Category Colors */
            --is-a-color: #333333;
            --part-of-color: #0077b6;
            --has-color: #d62828;
            --performs-color: #457b9d;
            --associates-color: #6a994e;
        }
        
        /* Base Typography */
        .ontology-generator-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 400;
            font-size: 1.125rem;
            line-height: 1.6;
            color: var(--ut-black);
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            background: linear-gradient(to bottom, white, var(--light-gray));
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }
        
        .content-wrapper {
            position: relative;
            padding: 40px;
        }
        
        /* Headings */
        .ontology-generator-container h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--utd-green);
            font-size: 2.8rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }
        
        .ontology-generator-container h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(to right, var(--utd-green), var(--utd-orange));
            border-radius: 2px;
        }
        
        .intro-text {
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: #555;
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 25px;
            box-sizing: border-box;
            width: 100%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--utd-green);
            font-size: 1.1rem;
        }
        
        .ontology-generator-container input[type="text"] {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            color: var(--ut-black);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background-color: white;
            outline: none;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .ontology-generator-container input[type="text"]:focus {
            border-color: var(--utd-green);
            box-shadow: 0 0 0 3px rgba(21, 71, 52, 0.1);
        }
        
        /* UTD Button */
        .submit-button {
            width: 100%;
            padding: 16px 26px;
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--utd-green) 0%, var(--utd-orange) 100%);
            text-transform: uppercase;
            text-align: center;
            border: none;
            border-radius: 8px;
            transition: 0.3s;
            cursor: pointer;
            font-family: inherit;
            box-sizing: border-box;
            box-shadow: 0 6px 12px rgba(21, 71, 52, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .submit-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--utd-orange) 0%, var(--utd-green) 100%);
            transition: 0.5s;
            z-index: -1;
        }
        
        .submit-button:hover:before {
            left: 0;
        }
        
        .submit-button:hover,
        .submit-button:focus {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(21, 71, 52, 0.3);
        }
        
        /* Results area */
        .result-container {
            margin-top: 40px;
            padding: 30px;
            background: white;
            border-radius: 12px;
            min-height: 100px;
            box-sizing: border-box;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }
        
        .domain-header, .visualization-header {
            color: var(--utd-green);
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 10px;
        }
        
        .domain-header:after, .visualization-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--utd-green), var(--utd-orange));
            border-radius: 1.5px;
        }
        
        /* Relationships container */
        .relationships-container {
            margin: 25px 0;
        }
        
        /* Individual relationship items */
        .relationship-item {
            background: linear-gradient(to right, rgba(21, 71, 52, 0.03) 0%, rgba(232, 117, 0, 0.03) 100%);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--utd-orange);
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 15px;
            font-size: 1rem;
            line-height: 1.5;
            position: relative;
            padding-top: 26px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s;
        }
        
        .relationship-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }
        
        .entity {
            font-weight: bold;
            color: var(--utd-orange);
        }
        
        .relation {
            font-style: italic;
            color: var(--utd-green);
            margin: 0 10px;
            font-weight: 500;
        }
        
        /* Relationship details dropdown */
        .relationship-details {
            display: none;
            background: white;
            border-radius: 8px;
            margin-top: 15px;
            padding: 25px;
            border: 1px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .relationship-details.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .detail-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #eee;
        }
        
        .detail-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .detail-section h4 {
            color: var(--utd-green);
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            padding-bottom: 5px;
            position: relative;
            display: inline-block;
        }
        
        .detail-section h4:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50%;
            height: 2px;
            background-color: var(--utd-orange-light);
            border-radius: 1px;
        }
        
        .detail-section p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
            color: #444;
        }
        
        .examples-list {
            padding-left: 20px;
            margin: 8px 0 0 0;
        }
        
        .examples-list li {
            margin-bottom: 8px;
            font-size: 0.98rem;
            position: relative;
            padding-left: 5px;
        }
        
        .examples-list li:before {
            content: "•";
            color: var(--utd-orange);
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }
        
        /* Click indicator */
        .relationship-item:after {
            content: 'Click for details';
            position: absolute;
            right: 15px;
            bottom: 12px;
            font-size: 0.75rem;
            color: #666;
            background: rgba(255, 255, 255, 0.8);
            padding: 3px 10px;
            border-radius: 12px;
            opacity: 0;
            transition: opacity 0.2s;
            border: 1px solid #f0f0f0;
        }
        
        .relationship-item:hover:after {
            opacity: 1;
        }
        
        .relationship-item.active:after {
            content: '';
            display: none;
        }
        
        /* Collapse button for active items */
        .relationship-details:before {
            content: 'Click to collapse';
            display: inline-block;
            text-align: center;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 15px;
            font-style: italic;
            background: #f5f5f5;
            padding: 5px 12px;
            border-radius: 16px;
            cursor: pointer;
            float: right;
            margin-left: auto;
            transition: background-color 0.2s, color 0.2s;
            border: 1px solid #eee;
        }
        
        .relationship-details:before:hover {
            background-color: #eee;
            color: #444;
        }
        
        .cardinality {
            font-size: 12px;
            color: #666;
            margin: 0 3px;
            background: #f0f0f0;
            padding: 1px 4px;
            border-radius: 3px;
        }
        
        .relationship-category {
            position: absolute;
            top: 0;
            right: 10px;
            font-size: 12px;
            color: #fff;
            font-style: normal;
            background: var(--utd-green-light);
            padding: 2px 8px;
            border-radius: 0 8px 0 8px;
            letter-spacing: 0.3px;
            font-weight: 500;
        }

        /* Custom styling for different relationship categories */
        .relationship-is-a .relationship-category {
            background-color: var(--is-a-color);
        }
        
        .relationship-part-of .relationship-category {
            background-color: var(--part-of-color);
        }
        
        .relationship-has .relationship-category {
            background-color: var(--has-color);
        }
        
        .relationship-performs .relationship-category {
            background-color: var(--performs-color);
        }
        
        .relationship-associates-with .relationship-category {
            background-color: var(--associates-color);
        }
        
        /* Category-specific styles */
        .relationship-is-a {
            border-left: 4px solid var(--is-a-color);
        }
        
        .relationship-part-of {
            border-left: 4px solid var(--part-of-color);
        }
        
        .relationship-has {
            border-left: 4px solid var(--has-color);
        }
        
        .relationship-performs {
            border-left: 4px solid var(--performs-color);
        }
        
        .relationship-associates-with {
            border-left: 4px solid var(--associates-color);
        }
        
        /* Mermaid container */
        .mermaid-container {
            max-width: 100%;
            overflow-x: auto;
            background-color: #1a2335; /* Darker, richer background */
            padding: 25px;
            border-radius: 10px;
            margin: 20px auto; /* Added auto to center horizontally */
            min-height: 500px;  /* Ensure minimum height */
            position: relative;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid #152030;
        }
        
        /* Custom styles for mermaid diagram */
        .mermaid-container .node rect {
            fill: var(--utd-orange);
            stroke: var(--utd-green);
            stroke-width: 2px;
        }
        
        .mermaid-container .edgeLabel {
            background-color: #1a2335;
            color: white;
            font-size: 14px;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        /* SVG styling for drag interaction */
        .mermaid svg {
            transform-origin: top left;
            transition: transform 0.2s ease;
            cursor: grab;
        }
        
        /* Change cursor when actively dragging */
        .mermaid svg.dragging {
            cursor: grabbing;
            transition: none; /* Remove transition during drag for smoother experience */
        }
        
        /* Diagram instructions */
        .diagram-instructions {
            font-size: 13px;
            color: #888;
            margin-top: 12px;
            font-style: italic;
            text-align: center;
            background: #f9f9f9;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        
        /* Loader - centered horizontally and vertically */
        .loader {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 30px auto;
            left: 0;
            right: 0;
        }
        
        .loader:before,
        .loader:after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--utd-green) 0%, var(--utd-orange) 100%);
            opacity: 0.8;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .loader:after {
            animation-delay: -1s;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(0);
                opacity: 0.8;
            }
            50% {
                transform: scale(1);
                opacity: 0;
            }
        }
        
        #loading-indicator {
            text-align: center;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin: 30px auto;
            max-width: 80%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            left: 0;
            right: 0;
        }
        
        #loading-indicator p {
            color: var(--utd-green);
            font-size: 1.2rem;
            margin-bottom: 20px;
            font-weight: 500;
            width: 100%;
            text-align: center;
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
        
        /* Legend for relationship types */
        .relationship-legend {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            font-size: 14px;
            border: 1px solid #eee;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        
        .legend-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--utd-green);
            font-size: 16px;
        }
        
        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 5px 10px;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            transition: transform 0.2s;
        }
        
        .legend-item:hover {
            transform: translateY(-2px);
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            margin-right: 8px;
            border-radius: 3px;
        }
        
        .legend-is-a {
            background-color: var(--is-a-color);
        }
        
        .legend-part-of {
            background-color: var(--part-of-color);
        }
        
        .legend-has {
            background-color: var(--has-color);
        }
        
        .legend-performs {
            background-color: var(--performs-color);
        }
        
        .legend-associates {
            background-color: var(--associates-color);
        }
        
        /* Zoom controls */
        .zoom-controls {
            margin-bottom: 15px;
            text-align: right;
        }
        
        .zoom-btn {
            background-color: var(--utd-green);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            margin: 0 3px;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .zoom-btn:hover {
            background-color: var(--utd-orange);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        /* Make diagram wrapper take full width */
        #mermaid-diagram {
            width: 100%;
            overflow: hidden;
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
                    curve: 'basis',
                    rankSpacing: 80,  // Increase spacing between ranks (rows)
                    nodeSpacing: 50   // Increase spacing between nodes
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
                        to: rel.to || "Unknown Target",
                        fromCardinality: rel.fromCardinality || "1",
                        toCardinality: rel.toCardinality || "1",
                        category: rel.category || "associates-with",
                        details: rel.details || null
                    };
                });
                
                // Display relationships
                if (relationships.length > 0) {
                    html += '<div class="relationships-container">';
                    
                    // Add legend
                    html += '<div class="relationship-legend">';
                    html += '<div class="legend-title">Relationship Types:</div>';
                    html += '<div class="legend-items">';
                    html += '<div class="legend-item"><div class="legend-color legend-is-a"></div>is-a</div>';
                    html += '<div class="legend-item"><div class="legend-color legend-part-of"></div>part-of</div>';
                    html += '<div class="legend-item"><div class="legend-color legend-has"></div>has</div>';
                    html += '<div class="legend-item"><div class="legend-color legend-performs"></div>performs</div>';
                    html += '<div class="legend-item"><div class="legend-color legend-associates"></div>associates-with</div>';
                    html += '</div></div>';
                    
                    relationships.forEach(function(rel, index) {
                        var categoryClass = 'relationship-' + rel.category;
                        
                        html += '<div class="relationship-item ' + categoryClass + '" data-index="' + index + '">';
                        html += '<div class="relationship-category">' + rel.category + '</div>';
                        html += '<span class="entity">' + rel.from + '</span>';
                        html += '<span class="cardinality from-cardinality">[' + rel.fromCardinality + ']</span>';
                        html += '<span class="relation">→ ' + rel.relationship + ' →</span>';
                        html += '<span class="cardinality to-cardinality">[' + rel.toCardinality + ']</span>';
                        html += '<span class="entity">' + rel.to + '</span>';
                        
                        // Add the details section (initially hidden)
                        html += '<div class="relationship-details">';
                        
                        // From entity definition
                        html += '<div class="detail-section">';
                        html += '<h4>' + rel.from + '</h4>';
                        if (rel.details && rel.details.from_definition) {
                            html += '<p>' + rel.details.from_definition + '</p>';
                        } else {
                            html += '<p>No definition available.</p>';
                        }
                        html += '</div>';
                        
                        // To entity definition
                        html += '<div class="detail-section">';
                        html += '<h4>' + rel.to + '</h4>';
                        if (rel.details && rel.details.to_definition) {
                            html += '<p>' + rel.details.to_definition + '</p>';
                        } else {
                            html += '<p>No definition available.</p>';
                        }
                        html += '</div>';
                        
                        // Relationship explanation
                        html += '<div class="detail-section">';
                        html += '<h4>Relationship: ' + rel.relationship + '</h4>';
                        if (rel.details && rel.details.relationship_explanation) {
                            html += '<p>' + rel.details.relationship_explanation + '</p>';
                        } else {
                            html += '<p>No explanation available.</p>';
                        }
                        html += '</div>';
                        
                        // Examples
                        if (rel.details && rel.details.examples && rel.details.examples.length > 0) {
                            html += '<div class="detail-section">';
                            html += '<h4>Examples:</h4>';
                            html += '<ul class="examples-list">';
                            rel.details.examples.forEach(function(example) {
                                html += '<li>' + example + '</li>';
                            });
                            html += '</ul>';
                            html += '</div>';
                        }
                        
                        // Significance (new section)
                        if (rel.details && rel.details.significance) {
                            html += '<div class="detail-section">';
                            html += '<h4>Significance:</h4>';
                            html += '<p>' + rel.details.significance + '</p>';
                            html += '</div>';
                        }
                        
                        html += '</div>'; // End of details
                        html += '</div>'; // End of relationship item
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
                
                // Make relationship items clickable to show/hide details
                $('.relationship-item').click(function() {
                    $(this).toggleClass('active');
                    $(this).find('.relationship-details').toggleClass('active');
                });
            }
            
            function generateMermaidDiagram(ontology) {
                if (!ontology || !Array.isArray(ontology.relationships) || ontology.relationships.length === 0) {
                    return;
                }
                
                // Get container width for scaling calculations later
                var containerWidth = $('#mermaid-diagram').width();
                
                // Create a mermaid flowchart definition
                var mermaidCode = 'flowchart LR\n';
                mermaidCode += '    %% Diagram sizing\n';
                mermaidCode += '    classDef default font-size:14px\n';
                
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
                
                // Define relationship styles based on category
                var categoryStyles = {
                    'is-a': 'stroke:#333333,stroke-width:2px',
                    'part-of': 'stroke:#0077b6,stroke-width:2px,stroke-dasharray:5 5',
                    'has': 'stroke:#d62828,stroke-width:1.5px',
                    'performs': 'stroke:#457b9d,stroke-width:1.5px',
                    'associates-with': 'stroke:#6a994e,stroke-width:1px'
                };
                
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
                
                // Define connections between nodes with cardinality
                ontology.relationships.forEach(function(rel, index) {
                    if (rel.from && rel.to && nodeIds[rel.from] && nodeIds[rel.to]) {
                        var fromId = nodeIds[rel.from];
                        var toId = nodeIds[rel.to];
                        var relationshipText = rel.relationship || "is related to";
                        
                        // Add cardinality to the relationship label
                        var fromCard = rel.fromCardinality || "1";
                        var toCard = rel.toCardinality || "1";
                        var labelWithCardinality = fromCard + ' ' + relationshipText + ' ' + toCard;
                        
                        // Create edge definition with the relationship as label including cardinality
                        mermaidCode += '    ' + fromId + ' -->|' + labelWithCardinality + '| ' + toId + '\n';
                        
                        // Apply style based on relationship category
                        var category = rel.category || 'associates-with';
                        var styleString = categoryStyles[category] || categoryStyles['associates-with'];
                        mermaidCode += '    linkStyle ' + index + ' ' + styleString + '\n';
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
                
                // Add zoom controls
                if (!$('#zoom-controls').length) {
                    var zoomControls = $('<div id="zoom-controls" class="zoom-controls">' +
                        '<button class="zoom-btn" id="zoom-in">+</button>' +
                        '<button class="zoom-btn" id="zoom-reset">Reset</button>' +
                        '<button class="zoom-btn" id="zoom-out">-</button>' +
                        '</div>');
                    $('#visualization-container').prepend(zoomControls);
                    
                    var currentZoom = 1;
                    
                    // Add dragging functionality
                    setTimeout(function() {
                        var svg = $('.mermaid svg')[0];
                        if (!svg) return;
                        
                        var dragStarted = false;
                        var lastX = 0;
                        var lastY = 0;
                        var translateX = 0;
                        var translateY = 0;
                        
                        // Helper function to set transform with both scale and translate
                        function setTransform() {
                            $(svg).css('transform', 
                                'translate(' + translateX + 'px, ' + translateY + 'px) scale(' + currentZoom + ')');
                        }
                        
                        // Handle mouse down to start dragging
                        $(svg).on('mousedown', function(event) {
                            if (event.which !== 1) return; // Only left mouse button
                            
                            dragStarted = true;
                            lastX = event.clientX;
                            lastY = event.clientY;
                            $(svg).addClass('dragging');
                            event.preventDefault();
                        });
                        
                        // Handle mouse move for dragging
                        $(document).on('mousemove', function(event) {
                            if (!dragStarted) return;
                            
                            // Calculate how much the mouse has moved
                            var deltaX = event.clientX - lastX;
                            var deltaY = event.clientY - lastY;
                            
                            // Update the last position
                            lastX = event.clientX;
                            lastY = event.clientY;
                            
                            // Update the translation
                            translateX += deltaX;
                            translateY += deltaY;
                            
                            // Apply the new transform
                            setTransform();
                        });
                        
                        // Handle mouse up to stop dragging
                        $(document).on('mouseup', function() {
                            dragStarted = false;
                            $(svg).removeClass('dragging');
                        });
                        
                        // Handle mouse leave to stop dragging
                        $(svg).on('mouseleave', function() {
                            if (dragStarted) {
                                dragStarted = false;
                                $(svg).removeClass('dragging');
                            }
                        });
                        
                        // Update zoom controls to maintain the translation when zooming
                        $('#zoom-in').off('click').on('click', function() {
                            currentZoom += 0.2;
                            setTransform();
                        });
                        
                        $('#zoom-out').off('click').on('click', function() {
                            currentZoom -= 0.2;
                            if (currentZoom < 0.5) currentZoom = 0.5;
                            setTransform();
                        });
                        
                        $('#zoom-reset').off('click').on('click', function() {
                            currentZoom = 1;
                            translateX = 0;
                            translateY = 0;
                            setTransform();
                        });
                        
                    }, 500);
                    
                    // Automatically set initial scale based on diagram complexity
                    setTimeout(function() {
                        var svg = $('.mermaid svg')[0];
                        if (!svg) return;
                        
                        var svgWidth = $(svg).width();
                        var containerWidth = $('#mermaid-diagram').width();
                        
                        if (svgWidth > containerWidth) {
                            var autoScale = Math.max(0.7, containerWidth / svgWidth);
                            $(svg).css('transform', 'scale(' + autoScale + ')');
                            currentZoom = autoScale;
                            
                            // Add auto-fit button if diagram is large
                            if (!$('#auto-fit').length) {
                                $('#zoom-controls').append('<button class="zoom-btn" id="auto-fit">Fit</button>');
                                $('#auto-fit').click(function() {
                                    var newScale = containerWidth / svgWidth * 0.95;
                                    currentZoom = newScale;
                                    translateX = 0; // Reset translation when fitting
                                    translateY = 0;
                                    $(svg).css('transform', 'translate(0px, 0px) scale(' + newScale + ')');
                                });
                            }
                        }
                        
                        // Center small diagrams
                        if (svgWidth < containerWidth * 0.8) {
                            $(svg).css({
                                'margin': '0 auto',
                                'display': 'block'
                            });
                        }
                    }, 500);
                }
            } // Close generateMermaidDiagram function
            
        }); // Close jQuery document ready function
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
    $python_path = 'python'; 
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
            <p>Users can enter a domain, and the plugin will generate relationship mappings using advanced LLMs, displayed both as a list and visual diagram with cardinality.</p>
        </div>
        
        <div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2>Requirements</h2>
            <p>This plugin requires:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>Python 3 installed on your server</li>
                <li>Ollama running with DeepSeek R1 model available (or alternative model configured in the Python script)</li>
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
