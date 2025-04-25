# Ontology-Generation LLM Project

An interactive application that automates the creation of domain ontologies during requirements elicitation, developed for The Fellows Consulting Group (FCG).

## Project Overview

This project combines a local LLM application using Ollama and DeepSeek R1 with a WordPress-based testing platform to streamline the requirements elicitation process through automated ontology generation.

## Repository Structure

The repository is organized into 3 main components:
- `llm/`: Contains the core LLM application using Ollama
- `website/`: Houses the WordPress-based testing platform
- `docs/`: Project documentation, technical specifications, and meeting notes

## Getting Started

### Prerequisites
- Python 3.8+
- WordPress development environment
- Ollama framework
- MySQL 5.7+

### Installation

1. Install Ollama Framework
  For Windows (Native)
  1. Download the installer
  2. Run the download .exe file and follow the installation prompts

  For WSL2:
  1. Ensure WSL2 is installed and configured
     # Run in PowerShell as Admin
     wsl --install
  2. Launch your distribution
  3. Install Ollama in WSL2
     curl -fsSL https://ollama.com/install.sh | sh
  4. Verify the installation
     ollama --version

  For macOS:
  1. Download the installer from https://ollama.com/download
  2. Open the downloaded .dmg file
  3. Drag the Ollama app to your Applications folder
  4. Launch Ollama from your Applications folder
  
  For Windows:
  Download and install from https://ollama.com/download

2. Pull the DeepSeek R1 7B Model
   After installing Ollama, pull the required model:
   ollama pull deepseek-r1:7b

3. Install Python Dependencies
   pip install requests

4. Set Up the Ontology Generator Python Script
   For macOS/WSL:
     1. Download the ontology_generator.py script from the repo
     2. Make the script executable:
        chmod +x ontology_generator.py
   For Windows(Native)
     1. Download the ontology_generator.py script from the repo
     2. Run script directly with python
        - In powershell python ontology_generator.py "domain"
  
5. WordPress Plugin Installation
   For macOS/WSL
   1. Create folder ontology-generator in your WordPress plugins directory (wp-content/plugins/)
   2. Copy both ontology_generator.php and ontology_generator.py to the folder
   3. Make the Python script executable
      chmod +x wp-content/plugins/ontology-generator/ontology_generator.py
   4. Ensure web server user has permission to execute the script
      chown www-data:www-data wp-content/plugins/ontology-generator/ontology_generator.py
   6. Log in to your WordPress admin panel
   7. Navigate to Plugins, then Installed Plugins
   8. Find "Ontology Generator (Visual)" and click "Activate"
  
   For Windows-hosted WordPress:
   1. Create a new folder called ontology-generator in your WordPress plugins directory
      (typically C:\path\to\wordpress\wp-content\plugins\)
   2. Copy both ontology_generator.php and ontology_generator.py to this folder
   3. Edit ontology_generator.php file to update the Python path:
      - Change $python_path = 'python3'; to $python_path = 'python';
   4. Log in to your WordPress admin panel
   5. Navigate to Plugins, then Installed Plugins
   6. Find "Ontology Generator (Visual)" and click Activate
      
Running the Application
Method 1: Command Line (Generate ontologyies directly from Command Line)
  For WSL/macOS
  1. Start Ollama service:
     - ollama serve
  2. In a new terminal, run the script with your chosen domain
     - python3 ontology_generator.py "domain"
  For Windows (Native)
  1. Start Ollama
  2. Open Command Prompt/ PowerShell and run:
     python ontology_generator.py "domain"
     
Method 2: WordPress Interface 
  1. Start Ollama:
     - ollama serve
  2. Create a new WordPress page or post
  3. Add the shortcode [ontology_visualizer] to the page an publis
  4. Ontology Generator Form should now be usable
       
## Development Guidelines
- All code changes must be submitted through pull requests
- Each PR requiresw two "ship-it" approvals from team members (excluding author)
- Maintain clear commit messages describing the changes
- Follow the established branching strategy:
  - main: Production-ready code
  - development: Integration branch
  - feature/*: Individual feature branches
  - hotfix/*: Emergency fixes

## Team Structure

The project is divided into two focused teams:
- Website Development Team
- LLM/Integration Team

## Future Development
  - MSI installer for Windows users (WIP)
      - Will simplify the installation process for Windows users
      - Will handle Python dependencies automatically
      - Will configure paths and perms 


