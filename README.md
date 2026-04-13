# Florix - AI-Powered Repository Analyzer

Florix is a code analysis tool designed to help developers and project managers understand their codebases through automated analysis and AI-driven insights. It provides a user-friendly interface to scan repositories and get a high-level overview of their functionality.

## 🚀 Features

- **Automated Code Parsing**: Scans Routes, Controllers, and Models to understand the application structure.
- **Dependency Exclusion**: Automatically skips folders like `node_modules`, `vendor`, `.venv`, and other non-core directories to focus on your actual code.
- **Flexible Input**: Supports analyzing repositories via ZIP file upload or by specifying a local directory path.
- **Real-time Progress Tracking**: Live status updates in the UI during both the parsing and AI generation phases.
- **AI-Driven Insights**: Uses local LLMs (via Ollama) to generate business-friendly explanations of what your code does.
- **Visual User Flows**: Automatically generates interactive Mermaid.js diagrams with zoom, pan, and fullscreen capabilities.
- **Smart Retries**: Re-generate AI explanations instantly using previously parsed data without re-scanning the entire codebase.
- **Source Code Browser**: Integrated file explorer to view your project structure and individual source files directly in the app.
- **Manual Control**: Includes a cancel button to stop stale or unwanted analysis processes.

## 🛠 Tech Stack

- **Framework**: Laravel 11.x
- **Language**: PHP 8.2+
- **Database**: SQLite
- **AI Engine**: [Ollama](https://ollama.com/) (running `phi3`)
- **Interactive Diagrams**: [Mermaid.js](https://mermaid.js.org/) with [svg-pan-zoom](https://github.com/ariutta/svg-pan-zoom)
- **Cache/Queue**: Redis
- **Infrastructure**: Docker & Docker Compose
- **Web Server**: Nginx

## 🏁 Getting Started

### Prerequisites

- Docker and Docker Compose
- (Optional) PHP 8.2+ and Composer for local development
- [Ollama](https://ollama.com/) installed and running (if not using the Dockerized AI service)

### Installation

1. **Clone the repository**:

    ```bash
    git clone <repository-url>
    cd florix
    ```

2. **Setup environment**:

    ```bash
    cp .env.example .env
    ```

3. **Start the environment**:

    ```bash
    docker compose up -d --build
    ```

4. **Install dependencies**:

    ```bash
    docker exec florix-app composer install
    ```

5. **Initialize application**:

    ```bash
    docker exec florix-app php artisan key:generate
    ```

6. **Run migrations**:
    ```bash
    docker exec florix-app php artisan migrate
    ```

## 📖 Usage

1. Open your browser and navigate to `http://localhost:8000`.
2. **Add a Project**: Provide a project name and choose between:
    - **ZIP Upload**: Upload a compressed version of your repository.
    - **Local Path**: Specify a path on your machine (must be accessible by the Docker container).
3. **Monitor Progress**: Watch the live status updates as the system extracts, parses, and analyzes your code.
4. **Interactive Analysis**: Explore the generated report through a tabbed interface:
    - **Core Features**: High-level business capabilities.
    - **What Your Users Will See**: UI/UX overview.
    - **The User Journey**: Step-by-step interaction flows.
    - **Process Flowchart**: Interactive, zoomable, and scrollable diagrams.
5. **Browse Code**: Use the "Browse Source Code" button to explore your repository directly within Florix.

## ⚙️ Configuration

- **Ollama Model**: By default, Florix now uses `phi3` for superior analysis and diagram generation. You can change this in your `.env` file via `OLLAMA_MODEL`.
- **Token Limits**: Configured for unlimited token generation to support large project analyses without truncation.
- **Resource Limits**: Ensure your Docker engine has sufficient RAM allocated for the `phi3` model.

## 🤝 Contribution

Feel free to open issues or submit pull requests for improvements.

## 📄 License

The Florix tool is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
