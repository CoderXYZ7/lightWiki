# LightWiki: A Space Biology Knowledge Engine

**LightWiki** is a lightweight, AI-powered wiki engine designed to meet the "Build a Space Biology Knowledge Engine" challenge for the NASA Space Apps Challenge. It provides a dynamic and intuitive platform for exploring and understanding NASA's vast collection of bioscience publications.

Demo
>[!ATENTION]
[http://bsl.mywire.org](http://bsl.mywire.org)

## The Challenge

NASA has generated a tremendous amount of information from decades of space biology experiments. This knowledge is crucial for future human space exploration, but it can be difficult for researchers and the public to find and understand the information relevant to their interests. The challenge is to build a dynamic dashboard that leverages AI and knowledge graphs to summarize and explore these publications.

## Our Solution: LightWiki

LightWiki addresses this challenge by transforming a set of NASA bioscience publications into an interactive and intelligent knowledge base. Our solution combines a simple and fast wiki engine with powerful AI-driven features to enable users to:

*   **Explore Connections:** Visualize the relationships between different research papers and concepts through an interactive 3D knowledge graph.
*   **Understand Complex Topics:** Get instant, AI-powered explanations of complex terms and concepts directly within the context of the research papers.
*   **Search with Intelligence:** Utilize an AI-powered search engine that understands the semantic meaning of queries, not just keywords.
*   **Chat with Documents:** Engage in a conversation with an AI assistant to ask questions and get answers about specific research papers.

## Features

### 1. Interactive 3D Knowledge Graph

LightWiki generates a 3D knowledge graph that visually represents the connections between different research papers. This allows users to explore the landscape of space biology research and discover new relationships and areas of study.

### 2. AI-Powered Contextual Explanations

When reading a research paper, users can simply highlight any text to instantly receive an AI-powered explanation of the selected term or concept in the context of the document. This feature, powered by the DeepSeek language model, makes complex scientific information more accessible to a wider audience.

### 3. Semantic Search

Our AI-powered search goes beyond simple keyword matching. By generating vector embeddings for each document, LightWiki's search engine can understand the semantic meaning of a user's query and return the most relevant results, even if the exact keywords are not present in the text.

### 4. Conversational AI Assistant

Users can open a chat window to have a conversation with an AI assistant about the research paper they are currently viewing. This allows for a more natural and intuitive way to explore and understand the content of the documents.

### 5. REST API

LightWiki includes a comprehensive REST API that allows for programmatic access to the knowledge base. This enables other applications and services to integrate with and build upon our platform.

## Technology Stack

*   **Backend:** PHP
*   **Data Storage:** Flat-file system using Markdown (`.md`) files
*   **AI Model:** DeepSeek API for contextual explanations and conversational AI
*   **Embeddings:** Generated using a Python script with sentence transformers
*   **3D Visualization:** three.js
*   **Frontend:** HTML, CSS, JavaScript

## How It Works

1.  **Data Import:** A script (`import_papers_data.php`) ingests the NASA bioscience publications from a JSON file, creating a separate Markdown file for each paper.
2.  **Embedding Generation:** A PHP script (`setup_embeddings.php`) orchestrates a Python script to generate vector embeddings for the content of each research paper. These embeddings are stored and used for the semantic search feature.
3.  **Knowledge Graph Generation:** The relationships between papers (e.g., shared authors, keywords, or concepts) are used to generate the `graph3d.json` file, which is then visualized using three.js.
4.  **AI Interaction:** The frontend uses JavaScript to capture selected text and send it to the `ai-meanings.php` endpoint, which then queries the DeepSeek API to get a contextual explanation. The chat feature works similarly, sending the user's questions and the document context to the AI.

## Getting Started

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/CoderXYZ7/lightWiki.git
    ```
2.  **Install dependencies:**
    ```bash
    composer install
    ```
3.  **Run the setup script:**
    ```bash
    php setup.php
    ```
4.  **Generate embeddings:**
    ```bash
    php setup_embeddings.php
    ```
5.  **Point your web server to the `public` directory.**

## Demo

A live demo of LightWiki is available at: [http://bsl.mywire.org](http://bsl.mywire.org)
