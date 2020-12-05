# Docker build files

Build the container using `make build` in the root folder.

- Dockerfile.local — a single container for development.  Contains PHP, Nginx, supervisord, uses files from the source repo.
- Dockerfile — self-contained image for production.
