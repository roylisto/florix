#!/bin/bash

# Start Ollama in the background
ollama serve &

# Wait for Ollama to start responding
echo "Waiting for Ollama to start..."
# The official ollama image doesn't have curl. We can use the ollama CLI itself to check if the server is up.
until ollama list > /dev/null 2>&1; do
  sleep 2
done

# Pull the model if it's not already there
MODEL_NAME=${OLLAMA_MODEL:-phi3}
echo "Ensuring model $MODEL_NAME is pulled..."
ollama pull $MODEL_NAME

echo "Ollama is ready!"

# Keep the container running and wait for the background process
wait
