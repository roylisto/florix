#!/bin/bash

# Start Ollama in the background
ollama serve &

# Wait for Ollama to start responding
echo "Waiting for Ollama to start..."
until curl -s http://localhost:11434/api/tags > /dev/null; do
  sleep 2
done

# Pull the model if it's not already there
MODEL_NAME=${OLLAMA_MODEL:-phi3}
echo "Ensuring model $MODEL_NAME is pulled..."
ollama pull $MODEL_NAME

echo "Ollama is ready!"

# Keep the container running and wait for the background process
wait
