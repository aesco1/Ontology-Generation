#!/usr/bin/env python3
import requests
import json
import sys
import re
import traceback
import os

class OllamaClient:
    
    def __init__(self, base_url="http://localhost:11434", cache_dir=None):
        self.api_generate = f"{base_url}/api/generate"
        self.current_domain = ""
        # where to store cache files; default: a "cache" folder next to this script
        self.cache_dir = cache_dir or os.path.join(os.path.dirname(__file__), "cache")
        os.makedirs(self.cache_dir, exist_ok=True)
    
    def extract_json_from_text(self, text):
        try:
            return json.loads(text)
        except json.JSONDecodeError:
            print(f"Error parsing entire text as JSON. Text (first 100 chars): {text[:100]}", file=sys.stderr)
            pass
        
        # Look for JSON between curly braces
        try:
            json_start = text.find('{')
            json_end = text.rfind('}') + 1
            
            if json_start >= 0 and json_end > json_start:
                json_str = text[json_start:json_end]
                return json.loads(json_str)
        except json.JSONDecodeError:
            print(f"Error parsing JSON between braces. Text (first 100 chars): {text[:100]}", file=sys.stderr)
            pass
        
        # Look for code blocks that might contain JSON
        code_block_pattern = r'```(?:json)?\s*([\s\S]*?)\s*```'
        matches = re.findall(code_block_pattern, text, re.DOTALL)
        
        for match in matches:
            try:
                return json.loads(match.strip())
            except json.JSONDecodeError:
                continue
        
        # Return a minimal valid structure if all else fails
        print(f"All JSON parsing attempts failed. Falling back to default ontology.", file=sys.stderr)
       
        return self.create_fallback_ontology(self.current_domain)
    
    def validate_relationship(self, rel):
        valid_cardinalities = ["1", "0..1", "0..*", "*", "1..*"]
        default_cardinality = "1"
        valid_categories = ["is-a", "part-of", "has", "performs", "associates-with"]
        default_category = "associates-with"
        
        if not isinstance(rel, dict):
            return None
            
        # Check required fields
        if "from" not in rel or "to" not in rel or "relationship" not in rel:
            return None
            
        # Normalize the relationship with default values if missing
        normalized = {
            "from": str(rel["from"]),
            "to": str(rel["to"]),
            "relationship": str(rel["relationship"]),
            "fromCardinality": rel.get("fromCardinality", default_cardinality),
            "toCardinality": rel.get("toCardinality", default_cardinality),
            "category": rel.get("category", default_category)
        }
        
        # Validate cardinalities
        if normalized["fromCardinality"] not in valid_cardinalities:
            normalized["fromCardinality"] = default_cardinality
        if normalized["toCardinality"] not in valid_cardinalities:
            normalized["toCardinality"] = default_cardinality
        
        # Validate category
        if normalized["category"] not in valid_categories:
            normalized["category"] = default_category
            
        return normalized
    
    def create_fallback_ontology(self, domain):
        return {
            "domain": domain,
            "relationships": [
                {"from": f"{domain.capitalize()} Entity", "to": f"{domain.capitalize()} Component", "relationship": "contains", "fromCardinality": "1", "toCardinality": "*", "category": "part-of"},
                {"from": f"{domain.capitalize()} Component", "to": f"{domain.capitalize()} Entity", "relationship": "is part of", "fromCardinality": "*", "toCardinality": "1", "category": "part-of"},
                {"from": f"{domain.capitalize()} Actor", "to": f"{domain.capitalize()} Resource", "relationship": "uses", "fromCardinality": "1", "toCardinality": "1..*", "category": "performs"},
                {"from": f"{domain.capitalize()} Process", "to": f"{domain.capitalize()} Output", "relationship": "produces", "fromCardinality": "1", "toCardinality": "0..*", "category": "has"}
            ]
        }
    
        """""
        Ensures all entities in the ontology are connected in a single graph.
        If disconnected components are found, adds relationships to connect them.
        """
    def validate_ontology_connectivity(self, ontology):
        
        
        # Build a graph representation
        graph = {}
        all_entities = set()
        
        # Collect all entities and build adjacency list
        for rel in ontology["relationships"]:
            from_entity = rel.get("from", "")
            to_entity = rel.get("to", "")
            
            if from_entity and to_entity:
                all_entities.add(from_entity)
                all_entities.add(to_entity)
                
                if from_entity not in graph:
                    graph[from_entity] = set()
                if to_entity not in graph:
                    graph[to_entity] = set()
                    
                graph[from_entity].add(to_entity)
                # For undirected connectivity analysis, add the reverse edge too
                graph[to_entity].add(from_entity)
        
        # Find connected components using BFS
        visited = set()
        components = []
        
        for entity in all_entities:
            if entity not in visited:
                # Found a new component
                component = set()
                queue = [entity]
                visited.add(entity)
                
                while queue:
                    current = queue.pop(0)
                    component.add(current)
                    
                    if current in graph:
                        for neighbor in graph[current]:
                            if neighbor not in visited:
                                visited.add(neighbor)
                                queue.append(neighbor)
                
                components.append(component)
        
        # If we have multiple components, connect them
        if len(components) > 1:
            domain = ontology.get("domain", "")
            print(f"Found {len(components)} disconnected components in ontology, connecting them.", file=sys.stderr)
            
            for i in range(len(components) - 1):
                # Select representative entities from components
                comp1 = list(components[i])[0]
                comp2 = list(components[i + 1])[0]
                
                # Create a connecting relationship
                connecting_rel = {
                    "from": comp1,
                    "to": comp2,
                    "relationship": f"relates to",
                    "fromCardinality": "0..1",
                    "toCardinality": "0..1",
                    "category": "associates-with"
                }
                
                # Add the connecting relationship
                ontology["relationships"].append(connecting_rel)
                print(f"Added connecting relationship: {comp1} -> {comp2}", file=sys.stderr)
        
        return ontology
    
    def generate_ontology(self, domain, num_ctx=4096):
        # 1) look for an existing cache file
        cache_file = os.path.join(self.cache_dir, f"{domain.lower()}.json")
        if os.path.isfile(cache_file):
            try:
                print(f"Loading ontology for '{domain}' from cache", file=sys.stderr)
                with open(cache_file, "r") as f:
                    return json.load(f)
            except Exception as e:
                print(f"Error loading from cache: {str(e)}, regenerating", file=sys.stderr)
                # corrupted cache? fall through and re-generate
                pass

        try:
            self.current_domain = domain
            try:
                requests.get("http://localhost:11434/api/tags", timeout=5)
            except requests.exceptions.RequestException as e:
                print(f"Error connecting to Ollama: {str(e)}", file=sys.stderr)
                return {"domain": domain, "error": "Cannot connect to Ollama service", "relationships": []}
            
            print(f"Generating ontology for domain: {domain}", file=sys.stderr)
            
            system_prompt = (
                "You are an expert in knowledge representation, ontology design, and formal modeling. "
                "Your task is to create detailed domain ontologies with explicit relationships and cardinality constraints. "
                "Provide ONLY valid JSON with no explanations or additional text."
            )
            
            user_prompt = f"""
            Create a comprehensive ontology for the domain of "{domain}" with explicit directional relationships and cardinality.

            Return a JSON structure with:
            1. A "domain" field with the domain name as string
            2. A "relationships" array of objects, each with:
               - "from": The source concept/entity
               - "to": The target concept/entity
               - "relationship": The action or relationship type that goes FROM source TO target
               - "fromCardinality": The cardinality constraint at the source (use "1", "0..1", "0..*", "1..*", or "*")
               - "toCardinality": The cardinality constraint at the target (use "1", "0..1", "0..*", "1..*", or "*")
               - "category": The relationship category ("is-a", "part-of", "has", "performs", "associates-with")

            Example cardinality notation:
            - "1": Exactly one
            - "0..1": Zero or one (optional)
            - "0..*" or "*": Zero or many
            - "1..*": One or many

            Example for university domain:
            {{
                "domain": "university",
                "relationships": [
                    {{
                        "from": "University", 
                        "to": "Department", 
                        "relationship": "contains",
                        "fromCardinality": "1",
                        "toCardinality": "1..*",
                        "category": "part-of"
                    }},
                    {{
                        "from": "Professor", 
                        "to": "Course", 
                        "relationship": "teaches",
                        "fromCardinality": "1",
                        "toCardinality": "1..*",
                        "category": "performs"
                    }}
                ]
            }}

            IMPORTANT: All entities in the ontology must be connected in a single graph. Make sure there are no isolated entities or subgraphs.

            Create 10-15 meaningful relationships for the domain of {domain}, capturing the essential concepts and their interactions.
            Return ONLY valid JSON like the example.
            """
            
            print(f"Sending request to Ollama", file=sys.stderr)
            response = requests.post(
                self.api_generate,
                json={
                    "model": "deepseek-r1:7b",
                    "prompt": user_prompt,
                    "system": system_prompt,
                    "stream": False,
                    "options": {"num_ctx": num_ctx}
                },
                timeout=120 
            )
            
            if response.status_code != 200:
                print(f"Error response from Ollama: {response.status_code}", file=sys.stderr)
                return self.create_fallback_ontology(domain)
                
            # Extract and parse the responsebye
            result = response.json().get("response", "")
            print(f"Received response from Ollama (first 100 chars): {result[:100]}", file=sys.stderr)
            
            ontology = self.extract_json_from_text(result)
            
            # Validation of relationships (ensure existence, and validate)
            # Ensure domain is set as a string
            if "domain" not in ontology or not isinstance(ontology["domain"], str):
                ontology["domain"] = domain
                
            # Ensure relationships exist
            if "relationships" not in ontology or not isinstance(ontology["relationships"], list) or len(ontology["relationships"]) == 0:
                print(f"No valid relationships found in response, using fallback", file=sys.stderr)
                ontology["relationships"] = self.create_fallback_ontology(domain)["relationships"]
                
            # Validate each relationship
            valid_relationships = []
            for rel in ontology.get("relationships", []):
                normalized_rel = self.validate_relationship(rel)
                if normalized_rel:
                    valid_relationships.append(normalized_rel)
            
            ontology["relationships"] = valid_relationships
            
            # Use fallback if no valid relationships
            if len(ontology["relationships"]) == 0:
                ontology["relationships"] = self.create_fallback_ontology(domain)["relationships"]

            # Ensure the ontology is fully connected
            ontology = self.validate_ontology_connectivity(ontology)
                
            # Enhance ontology with additional information for each relationship
            ontology = self.enhance_relationships(ontology)
            
            # 2) write the fresh result to cache
            try:
                print(f"Writing ontology for '{domain}' to cache", file=sys.stderr)
                with open(cache_file, "w") as f:
                    json.dump(ontology, f, indent=2)
            except Exception as e:
                print(f"Error writing to cache: {str(e)}", file=sys.stderr)
                pass
                
            return ontology
        
        # return error and fallback ontology
        except Exception as e:
            print(f"Error generating ontology: {str(e)}", file=sys.stderr)
            traceback.print_exc(file=sys.stderr)
            
            fallback = self.create_fallback_ontology(domain)
            fallback["error"] = str(e)
            return fallback
    
    def enhance_relationships(self, ontology):
        """
        Adds detailed information for each relationship in the ontology.
        Uses batching to reduce the number of API calls.
        """
        print(f"Enhancing ontology with details for each relationship...", file=sys.stderr)
        domain = ontology.get("domain", "unknown")
        
        # Get all relationships for batching
        relationships = ontology.get("relationships", [])
        if not relationships:
            return ontology
            
        # Use batching - process multiple relationships in single API call (2-3 per batch)
        BATCH_SIZE = 2  # Process 2 relationships at a time for more comprehensive information
        
        for i in range(0, len(relationships), BATCH_SIZE):
            batch = relationships[i:i+BATCH_SIZE]
            batch_prompts = []
            
            # Create prompts for each relationship in the batch
            for rel in batch:
                from_entity = rel.get("from", "Entity")
                to_entity = rel.get("to", "Entity")
                relationship = rel.get("relationship", "relates to")
                
                # Create a more detailed prompt for richer information
                rel_prompt = (
                    f"Relationship: {from_entity} {relationship} {to_entity}\n"
                    f"Analyze in depth with comprehensive definitions and examples."
                )
                batch_prompts.append(rel_prompt)
            
            # Combine all prompts in the batch with requests for more depth
            combined_prompt = f"""
            For the domain of {domain}, provide in-depth information about the following relationships:
            
            {'\n\n'.join(batch_prompts)}
            
            For EACH relationship, provide:
            
            1. A thorough definition of the source entity (2-3 sentences) - explain its key characteristics, purpose, and role in the {domain} domain
            
            2. A thorough definition of the target entity (2-3 sentences) - explain its key characteristics, purpose, and role in the {domain} domain
            
            3. A comprehensive explanation of their relationship (3-4 sentences) - describe how these entities interact, the nature of their relationship, constraints, and implications
            
            4. 2-3 specific examples showing this relationship in real-world scenarios, with context and impact
            
            5. The significance of this relationship in the {domain} domain (1-2 sentences) - why this relationship matters
            
            Format as a JSON array of objects matching this structure:
            [
                {{
                    "relationship": "entity1 action entity2",
                    "from_definition": "Thorough definition of entity1...",
                    "to_definition": "Thorough definition of entity2...",
                    "relationship_explanation": "Comprehensive explanation of the relationship...",
                    "examples": ["Example 1 with context...", "Example 2 with context..."],
                    "significance": "Why this relationship matters..."
                }},
                // next relationship...
            ]
            
            Provide substantial detail in each section. Output ONLY valid JSON without extra text.
            """
            
            try:
                # Query Ollama with the batch prompt, allowing more tokens for detailed responses
                system = "You are an expert ontology analyst who provides comprehensive definitions and detailed explanations of relationships between entities, focusing on depth and clarity."
                response = requests.post(
                    self.api_generate,
                    json={
                        "model": "deepseek-r1:7b",
                        "system": system,
                        "prompt": combined_prompt,
                        "stream": False,
                        "options": {
                            "temperature": 0.3,  # Slightly higher temperature for more detailed responses
                            "top_p": 0.9,
                            "num_ctx": 4096      # Increased context size for more detailed responses
                        }
                    },
                    timeout=120  # Increased timeout for more comprehensive generation
                )
                
                if response.status_code != 200:
                    raise Exception(f"LLM error {response.status_code}")
                
                raw_response = response.json().get("response", "")
                batch_results = self.extract_json_from_text(raw_response)
                
                # Process and assign results back to the relationships
                if isinstance(batch_results, list) and len(batch_results) > 0:
                    for j, rel in enumerate(batch):
                        if j < len(batch_results):
                            result = batch_results[j]
                            # Normalize the result structure with the new significance field
                            rel["details"] = {
                                "from_definition": result.get("from_definition", f"Definition of {rel.get('from', 'Entity')}"),
                                "to_definition": result.get("to_definition", f"Definition of {rel.get('to', 'Entity')}"),
                                "relationship_explanation": result.get("relationship_explanation", f"How {rel.get('from', 'Entity')} {rel.get('relationship', 'relates to')} {rel.get('to', 'Entity')}"),
                                "examples": result.get("examples", [f"Example of {rel.get('from', 'Entity')} {rel.get('relationship', 'relates to')} {rel.get('to', 'Entity')}"]),
                                "significance": result.get("significance", f"Significance of the relationship between {rel.get('from', 'Entity')} and {rel.get('to', 'Entity')}")
                            }
                        else:
                            # Create minimal details for missing results
                            self._add_default_details(rel)
                else:
                    # If batch processing failed, add default details to each relationship
                    for rel in batch:
                        self._add_default_details(rel)
                        
            except Exception as e:
                print(f"Error enhancing relationship batch: {str(e)}", file=sys.stderr)
                # Add minimal details on error
                for rel in batch:
                    self._add_default_details(rel)
        
        return ontology
    
    def _add_default_details(self, rel):
        """Helper method to add default details to a relationship"""
        from_entity = rel.get("from", "Entity")
        to_entity = rel.get("to", "Entity")
        relationship = rel.get("relationship", "relates to")
        
        rel["details"] = {
            "from_definition": f"Definition of {from_entity}",
            "to_definition": f"Definition of {to_entity}",
            "relationship_explanation": f"How {from_entity} {relationship} {to_entity}",
            "examples": [f"Example of {from_entity} {relationship} {to_entity}"],
            "significance": f"Significance of the relationship between {from_entity} and {to_entity}"
        }

def main():
    """Command line interface for the ontology generator."""
    import argparse
    
    parser = argparse.ArgumentParser(description='Generate domain ontologies using advanced LLMs')
    parser.add_argument('domain', help='The domain for which to generate an ontology')
    parser.add_argument('--context', '-c', type=int, default=4096, help='Context window size (default: 4096)')
    
    args = parser.parse_args()
    
    try:
        client = OllamaClient()
        ontology = client.generate_ontology(args.domain, args.context)
        
        if isinstance(ontology, dict) and "domain" in ontology and not isinstance(ontology["domain"], str):
            ontology["domain"] = str(args.domain)
            
        print(json.dumps(ontology))
    except Exception as e:
        print(json.dumps({
            "error": f"Error: {str(e)}",
            "domain": args.domain,
            "relationships": []
        }))

if __name__ == "__main__":
    main()
