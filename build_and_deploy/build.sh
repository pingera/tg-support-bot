#!/bin/bash

set -ex

CURRENT_DIR="${PWD##*/}"
TAG="${1}"

# Ensure a tag is provided
if [[ -z "${TAG}" ]]; then
  echo "Error: Please provide a tag as the first argument."
  exit 1
fi

REGISTRY=""
IMAGE_NAME="pingera-support-bot"

docker build -t ${REGISTRY}/${IMAGE_NAME}:${TAG} -t ${REGISTRY}/${IMAGE_NAME}:latest .
docker push ${REGISTRY}/${IMAGE_NAME}
docker push ${REGISTRY}/${IMAGE_NAME}:${TAG}
