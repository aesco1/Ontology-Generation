import requests
import json
import argparse
from typing import Dict, List, Any, Optional


class OllamaClient:
    """Client for interacting with a local Ollama instance."""
    
    def __init__(self, base_url: str = "http://localhost:11434"):
        """
        Initialize the Ollama client.
        
        Args:
            base_url: The base URL of the Ollama API. Default is http://localhost:11434.
        """
        self.base_url = base_url
        self.api_generate = f"{base_url}/api/generate"
        
    def generate_ontology(self, domain: str, model: str = "llama3.2", format: str = "json") -> Dict[str, Any]:
        """
        Generate an ontology for a given domain with specific labeled relationships.
        
        Args:
            domain: The domain for which to generate an ontology
            model: The model to use for generation. Default is "llama3.2".
            format: The desired output format. Default is "json".
            
        Returns:
            The generated ontology as a dictionary with descriptive relationship labels
        """
        # Construct the prompt for ontology generation
        system_prompt = (
            "You are an expert in knowledge representation and semantic web technologies. "
            "Your task is to create a comprehensive domain ontology with specific, descriptive relationship labels. "
            "Always use precise relationship labels that follow the pattern 'Has X', 'Is X of', 'Contains X', 'Belongs to X', etc. "
            "For example, use 'Has publisher' instead of 'published by', use 'Written by' instead of 'author is'. "
            "Relationships should be labeled in a way that clearly identifies the nature and direction of the relationship."
        )
        
        user_prompt = f"""
        Please create an ontology for the domain of {domain}.
        
        The ontology should include:
        1. Main concepts/classes in the domain
        2. Specific, labeled relationships/properties between these concepts (e.g., "Has publisher", "Written by", "Contains")
        3. Attributes of the concepts
        4. Hierarchical structure (subclass relationships)
        5. A few key instances or examples
        
        IMPORTANT: Make sure all relationships have specific, descriptive labels that clearly indicate the nature of the relationship. For example, use "Has publisher" instead of just "related to" or "has". Use "Written by" instead of "author". Use action verbs and descriptive phrases.
        
        Return the result in {format} format with the following structure:
        {{
            "domain": "{domain}",
            "concepts": [
                {{
                    "name": "concept_name",
                    "description": "concept_description",
                    "subclasses": ["subclass1", "subclass2"],
                    "attributes": ["attr1", "attr2"],
                    "relationships": [
                        {{"relatedTo": "other_concept", "relationship": "Has specific relationship"}}
                    ]
                }}
            ],
            "instances": [
                {{
                    "name": "instance_name",
                    "type": "concept_name",
                    "properties": {{"property1": "value1"}}
                }}
            ]
        }}
        """
        
        # Prepare the request payload
        payload = {
            "model": model,
            "prompt": user_prompt,
            "system": system_prompt,
            "stream": False
        }
        
        # Send the request to Ollama
        response = requests.post(self.api_generate, json=payload)
        
        if response.status_code != 200:
            raise Exception(f"Error from Ollama API: {response.text}")
        
        # Extract the response
        result = response.json()
        response_text = result.get("response", "")
        
        # Parse the JSON output from the response
        try:
            # Extract JSON from the response (handling potential extra text)
            json_start = response_text.find('{')
            json_end = response_text.rfind('}') + 1
            if json_start >= 0 and json_end > json_start:
                json_str = response_text[json_start:json_end]
                return json.loads(json_str)
            else:
                raise ValueError("No JSON found in response")
        except json.JSONDecodeError:
            # If JSON parsing fails, return the raw text
            return {"raw_response": response_text}
    


def main():
    """Command line interface for the ontology generator."""
    parser = argparse.ArgumentParser(description="Generate ontologies using Ollama llama3.2 model")
    parser.add_argument("domain", help="The domain for which to generate an ontology")
    parser.add_argument("--format", default="json", choices=["json", "text"], help="Output format")
    parser.add_argument("--output", help="Output file path (if not specified, prints to console)")
    
    args = parser.parse_args()
    
    # Create the client
    client = OllamaClient()
    
    # Generate the ontology
    try:
        print(f"Generating ontology for domain: {args.domain} using model: llama3.2 ...")
        ontology = client.generate_ontology(args.domain, "llama3.2", args.format)
        
        # Output the result
        output_text = json.dumps(ontology, indent=2)
        
        if args.output:
            with open(args.output, 'w') as f:
                f.write(output_text)
            print(f"Ontology saved to {args.output}")
        else:
            print("\nGenerated Ontology:")
            print(output_text)
            
    except Exception as e:
        print(f"Error generating ontology: {e}")


if __name__ == "__main__":
    main()
