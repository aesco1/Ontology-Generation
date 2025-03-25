#!/usr/bin/env python3
import requests
import json
import sys
import re
import traceback

class OllamaClient:
    
    def __init__(self, base_url="http://localhost:11434"):
        self.api_generate = f"{base_url}/api/generate"
    
    def extract_json_from_text(self, text):
        """ Extract valid JSON from text response
            Use different methods (parse the entire text as JSON),
            Looking in between outermost braces, Look in code blocks,
            if not fallback ontology is used """
        
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
    
    def create_fallback_ontology(self, domain):
        return {
            "domain": domain,
            "relationships": [
                {"from": f"{domain.capitalize()} Entity", "to": f"{domain.capitalize()} Component", "relationship": "contains"},
                {"from": f"{domain.capitalize()} Component", "to": f"{domain.capitalize()} Entity", "relationship": "is part of"},
                {"from": f"{domain.capitalize()} Actor", "to": f"{domain.capitalize()} Resource", "relationship": "uses"},
                {"from": f"{domain.capitalize()} Process", "to": f"{domain.capitalize()} Output", "relationship": "produces"}
            ]
        }
    
    def generate_ontology(self, domain, num_ctx=4096):
        try:
            self.current_domain = domain
            try:
                requests.get("http://localhost:11434/api/tags", timeout=5)
            except requests.exceptions.RequestException as e:
                print(f"Error connecting to Ollama: {str(e)}", file=sys.stderr)
                return {"domain": domain, "error": "Cannot connect to Ollama service", "relationships": []}
            
            print(f"Generating ontology for domain: {domain}", file=sys.stderr)
            
            system_prompt = (
                "You are an expert in knowledge representation and ontology design. "
                "Your task is to create a list of clear, directed relationships between concepts. "
                "You will provide ONLY valid JSON with no explanations."
            )
            
            user_prompt = f"""
            Create a simplified ontology for the domain of "{domain}" with explicit directional relationships.
            
            Return a JSON structure with:
            1. A "domain" field with the domain name as string
            2. A "relationships" array of objects, each with:
               - "from": The source concept
               - "to": The target concept
               - "relationship": The action or relationship that goes FROM source TO target
            
            Example for education:
            {{
                "domain": "education",
                "relationships": [
                    {{"from": "Professor", "to": "Course", "relationship": "teaches"}},
                    {{"from": "Student", "to": "Course", "relationship": "enrolls in"}},
                    {{"from": "University", "to": "Professor", "relationship": "employs"}},
                    {{"from": "University", "to": "Degree", "relationship": "awards"}}
                ]
            }}
            
            Create 10-15 meaningful relationships for the domain of {domain}.
            Return ONLY valid JSON like the example.
            """
            
            print(f"Sending request to Ollama", file=sys.stderr)
            response = requests.post(
                self.api_generate,
                json={
                    "model": "llama3.2",
                    "prompt": user_prompt,
                    "system": system_prompt,
                    "stream": False,
                    "options": {"num_ctx": num_ctx}
                },
                timeout=120
            )
            
            # Check for Ollama API errors
            if response.status_code != 200:
                print(f"Error response from Ollama: {response.status_code}", file=sys.stderr)
                return self.create_fallback_ontology(domain)
                
            # Extract and parse the response
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
                
            # Validate that each relationships has reqd fields
            valid_relationships = []
            for rel in ontology.get("relationships", []):
                if isinstance(rel, dict) and "from" in rel and "to" in rel and "relationship" in rel:
                    valid_relationships.append(rel)
            
            ontology["relationships"] = valid_relationships
            
            # Use fallback if no valid relationsips
            if len(ontology["relationships"]) == 0:
                ontology["relationships"] = self.create_fallback_ontology(domain)["relationships"]
                
            return ontology
        
        except Exception as e:
            print(f"Error generating ontology: {str(e)}", file=sys.stderr)
            traceback.print_exc(file=sys.stderr)
            
            fallback = self.create_fallback_ontology(domain)
            fallback["error"] = str(e)
            return fallback

def main():
    import argparse
    
    parser = argparse.ArgumentParser(description='Generate domain ontologies using Llama 3.2')
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
