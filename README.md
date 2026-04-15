# AI Profile Structurer

Structured Data Extraction Pipeline for Professional Talent Acquisition

Laravel 13 React & Inertia.js PostgreSQL NVIDIA NIM (Kimi 2.5) Redis & Horizon

## Project Overview

The **AI Profile Structurer** is a full-stack engineering solution designed to bridge the gap between unstructured professional data and machine-learning-ready relational databases.

As AI becomes central to talent acquisition, the challenge remains data quality. This application automates the parsing of raw profile text into a rigid JSON schema while implementing a **"Human-in-the-Loop"** validation layer. This ensures that the downstream data consumed by ML algorithms is verified, cleaned, and structured.

## Key Engineering Features

- **LLM-Powered Parsing:** Utilizes NVIDIA NIM (Kimi 2.5) to perform zero-shot extraction of entities (Experience, Skills, Education) from unstructured text.
- **Human-in-the-Loop (HITL):** Interactive React interface that allows users to review, correct, and augment AI-generated JSON before persistence.
- **Asynchronous Processing:** Background job handling via Redis and Laravel Horizon to manage LLM API latency and ensure a responsive UI.
- **Relational Integrity:** Complex schema design in PostgreSQL to support many-to-many relationships (Profiles to Skills) and nested experiences.

## Technical Stack

### Backend (The Engine)

Built with **Laravel 13**, leveraging the framework's modern AI SDK and optimized service containers. Use of PostgreSQL with **JSONB** support allows for future-proofing semi-structured data storage.

### Frontend (The Interface)

Developed with **React** via **Inertia.js**. This architecture provides the performance of a Single Page Application (SPA) with the security and routing simplicity of a classic monolithic framework.

### Infrastructure

Containerized using **Laravel Sail** (Docker). Background workers are managed by **Redis**, providing robust queue management for high-latency AI requests.

## The Algorithm & Workflow

RAW TEXT INPUT (Manual Paste)  
   → Dispatch Background Job (Redis)  
     → Request NVIDIA NIM Inference (Kimi 2.5)  
       → Return Structured JSON  
         → React UI Review & Edit  
           → Final Persistence (PostgreSQL)

## Future ML Roadmap In Progress

This data pipeline is the foundational phase for a larger Data Science initiative:

- **Vector Embeddings:** Transitioning profile data into high-dimensional vectors for semantic search.
- **Recommendation Engine:** Building a cosine-similarity model to match candidates with job descriptions.
- **Skill Gap Analysis:** Using historical data to predict career trajectory based on skill clusters.

## Privacy & Ethics

_This project is a technical demonstration of data processing capabilities. All data processed is manually input by the user. No automated scraping of LinkedIn is performed, adhering to platform Terms of Service and emphasizing ethical AI data handling._

Built with Laravel 13 & React | 2026 Portfolio Project
