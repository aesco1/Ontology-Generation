# Ontology-Generation LLM Project

An interactive application that automates the creation of domain ontologies during requirements elicitation, developed for The Fellows Consulting Group (FCG).

## Project Overview

This project combines a local LLM application using Ollama and DeepSeek R1 with a WordPress-based testing platform to streamline the requirements elicitation process through automated ontology generation.

## Repository Structure

The repository is organized into 3 main components with platform-specific versions for both Linux/WSL and Windows:

~~~
ontology-generator/
├── docs/                  # Project documentation, technical specifications, and meeting notes
├── llm/                   # Contains the core LLM application using Ollama
│   ├── ontology_generator.py             # Linux/WSL version
│   ├── ontology_generator_windows.py     # Windows-specific version
│   └── cache/                            # Generated ontology cache directory
└── website/               # Houses the WordPress-based testing platform
    ├── ontology-visualizer.php           # Linux/WSL version of WordPress plugin
    ├── ontology-visualizer_windows.php   # Windows-specific version of WordPress plugin
~~~

## Platform-Specific Files

This project provides separate implementations for Linux/WSL and Windows environments:

- **Linux/WSL:** 
  - Use `ontology_generator.py` for the LLM component
  - Use `ontology-visualizer.php` for the WordPress plugin

- **Windows:** 
  - Use `ontology_generator_windows.py` for the LLM component
  - Use `ontology-visualizer_windows.php` for the WordPress plugin

## Prerequisites
- Python 3.8+
- WordPress development environment
- Ollama framework
- MySQL 5.7+

## Installation

### 1. Install Ollama Framework

**For Windows (Native)**
1. Download the installer from [https://ollama.com/download](https://ollama.com/download)
2. Run the downloaded .exe file and follow the installation prompts
3. Start Ollama from the Start menu or desktop shortcut

**For WSL2**
1. Ensure WSL2 is installed and configured
   ~~~
   # Run in PowerShell as Admin
   wsl --install

2. Launch your distribution
3. Install Ollama in WSL2
   ~~~
   curl -fsSL https://ollama.com/install.sh | sh
   ~~~
4. Verify the installation
   ~~~
   ollama --version
   ~~~

**For macOS**
1. Download the installer from [https://ollama.com/download](https://ollama.com/download)
2. Open the downloaded .dmg file
3. Drag the Ollama app to your Applications folder
4. Launch Ollama from your Applications folder

### 2. Pull the DeepSeek R1 7B Model

After installing Ollama, pull the required model:
~~~
ollama pull deepseek-r1:7b
~~~

### 3. Install Python Dependencies
~~~
pip install requests
~~~

### 4. Set Up Web Server Environment

**For WSL/Linux**
1. Install Apache, MySQL, and PHP:
   ~~~
   sudo apt update
   sudo apt install apache2 mysql-server php php-mysql
   ~~~

2. Start and enable the services:
   ~~~
   sudo systemctl start apache2
   sudo systemctl enable apache2
   sudo systemctl start mysql
   sudo systemctl enable mysql
   ~~~

3. Secure MySQL installation:
   ~~~
   sudo mysql_secure_installation
   ~~~

4. Create a database for WordPress:
   ~~~
   sudo mysql -u root -p
   CREATE DATABASE wordpress;
   CREATE USER 'wordpress_user'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON wordpress.* TO 'wordpress_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ~~~

**For Windows (XAMPP)**
1. Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Start Apache and MySQL from XAMPP Control Panel
3. Create a database for WordPress using phpMyAdmin:
   - Open http://localhost/phpmyadmin/
   - Click "New" in the left sidebar
   - Enter "wordpress" as the database name
   - Click "Create"

### 5. Set Up the Ontology Generator Python Script

**For macOS/WSL**
- Use the `ontology_generator.py` script from the `llm/` directory
- Make the script executable:
  ~~~
  chmod +x ontology_generator.py
  ~~~

**For Windows (Native)**
- Use the `ontology_generator_windows.py` script from the `llm/` directory
- The Windows version includes specific adaptations for the Windows environment
- Run script directly with python:
  ~~~
  python ontology_generator_windows.py "domain"
  ~~~

### 6. WordPress Installation

**For WSL/Linux**
1. Download and extract WordPress:
   ~~~
   cd /var/www/html
   sudo wget https://wordpress.org/latest.tar.gz
   sudo tar -xzvf latest.tar.gz
   sudo mv wordpress ontology
   sudo chown -R www-data:www-data ontology
   sudo chmod -R 755 ontology
   ~~~

2. Configure WordPress:
   - Navigate to http://localhost/ontology in your browser
   - Follow the setup wizard, using the database credentials you created earlier

**For Windows (XAMPP)**
1. Download WordPress from [https://wordpress.org/download/](https://wordpress.org/download/)
2. Extract to C:\xampp\htdocs\ontology
3. Navigate to http://localhost/ontology in your browser
4. Follow the setup wizard, using the database credentials you created earlier

### 7. WordPress Plugin Installation

**For macOS/WSL**
1. Create folder `ontology-generator` in your WordPress plugins directory (wp-content/plugins/)
2. Copy both `ontology-visualizer.php` and `ontology_generator.py` to the folder
3. Make the Python script executable
   ~~~
   chmod +x wp-content/plugins/ontology-generator/ontology_generator.py
   ~~~
4. Ensure web server user has permission to execute the script
   ~~~
   sudo chown www-data:www-data wp-content/plugins/ontology-generator/ontology_generator.py
   ~~~
5. Log in to your WordPress admin panel
6. Navigate to Plugins, then Installed Plugins
7. Find "Ontology Generator (Visual)" and click "Activate"

**For Windows-hosted WordPress**
1. Create a new folder called `ontology-generator` in your WordPress plugins directory
   (typically C:\xampp\htdocs\wordpress\wp-content\plugins\)
2. Copy `ontology_generator_windows.py` to this folder and rename it to `ontology_generator.py`
3. Copy `ontology-visualizer_windows.php` to this folder and rename it to `ontology-visualizer.php`
4. Edit the PHP file if needed to ensure the Python path is correctly set
5. Ensure proper file permissions (the web server user needs to be able to execute the Python script)
6. Log in to your WordPress admin panel
7. Navigate to Plugins, then Installed Plugins
8. Find "Ontology Generator (Visual)" and click "Activate"

## Key Differences Between Windows and Linux Versions

### Python Scripts
- **Path handling**: Windows version uses backslashes vs. forward slashes in Linux
- **Python command**: Windows typically uses `python` vs. `python3` in Linux
- **File permissions**: Different handling between Windows and Linux

### PHP Files
- **Python execution**: Windows version includes specific adaptations for Windows file paths
- **Process handling**: Different methods for spawning Python processes
- **Error handling**: Platform-specific error detection and reporting

## Running the Application

### Method 1: Command Line (Generate ontologies directly from Command Line)

**For WSL/macOS**
1. Start Ollama service:
   ~~~
   ollama serve
   ~~~
2. In a new terminal, run the script with your chosen domain
   ~~~
   python3 ontology_generator.py "domain"
   ~~~

**For Windows (Native)**
1. Start Ollama
2. Open Command Prompt/PowerShell and run:
   ~~~
   python ontology_generator_windows.py "domain"
   ~~~
   - Output will be displayed as JSON in the terminal
   - A cache file will be created in the `cache` directory next to the script

### Method 2: WordPress Interface
1. Ensure all services are running:
   - **For WSL/Linux**: 
     ~~~
     sudo systemctl start apache2
     sudo systemctl start mysql
     ollama serve
     ~~~
   - **For Windows**: 
     - Start Apache and MySQL from XAMPP Control Panel
     - Ensure Ollama is running in the background

2. Create a new WordPress page or post
3. Add the shortcode `[ontology_visualizer]` to the page and publish
4. Ontology Generator Form should now be usable:
   - Enter a domain keyword in the form
   - Click "Generate Relationships"
   - View the results in both list and visual formats

## Troubleshooting

### Windows-Specific Issues
- **"Python not found" errors**: Ensure Python is in your system PATH
  - Go to System Properties → Environment Variables → Path → Add Python installation directory
- **Script execution policy restrictions**: You may need to set PowerShell execution policy
  ~~~
  Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
  ~~~
- **Ollama connection issues**: Verify Ollama is running and accessible at http://localhost:11434
  - Check Task Manager to confirm Ollama is running
  - Try accessing http://localhost:11434/api/tags in your browser
- **WordPress plugin errors**: Check PHP error logs in your web server
  - For XAMPP: Check logs in xampp/apache/logs/error.log
- **Apache/MySQL not starting**: Check for port conflicts
  - Services like Skype may use port 80
  - Use Task Manager to identify and close conflicting applications

### WSL-Specific Issues
- **Apache/MySQL service issues**: Check service status and logs
  ~~~
  sudo systemctl status apache2
  sudo systemctl status mysql
  sudo tail -f /var/log/apache2/error.log
  ~~~
- **Connectivity issues**: If WordPress can't connect to Ollama in WSL
  - Try running Ollama with specific binding: `OLLAMA_HOST=0.0.0.0 ollama serve`
  - Configure port forwarding to access WSL services from Windows
- **Permission issues**: Ensure proper ownership and execution permissions
  ~~~
  sudo chown www-data:www-data ontology_generator.py
  sudo chmod +x ontology_generator.py
  ~~~
- **WSL filesystem permissions**: Sometimes Windows permissions can affect WSL
  ~~~
  sudo chown -R www-data:www-data /var/www/html/ontology
  sudo chmod -R 755 /var/www/html/ontology
  ~~~

## Development Guidelines
- All code changes must be submitted through pull requests
- Each PR requires two "ship-it" approvals from team members (excluding author)
- Maintain clear commit messages describing the changes
- Follow the established branching strategy:
  - main: Production-ready code
  - development: Integration branch
  - feature/*: Individual feature branches
  - hotfix/*: Emergency fixes
- Follow the existing code style and documentation practices

## Team Structure

The project is divided into two focused teams:
- Website Development Team
- LLM/Integration Team

## Future Development
- MSI installer for Windows users (WIP)
  - Will simplify the installation process for Windows users
  - Will handle Python dependencies automatically
  - Will configure paths and permissions
