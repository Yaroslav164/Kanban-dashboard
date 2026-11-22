#!/bin/sh
set -eu

if [ "$POSTGRES_DB" != kanban_testing ]; then
    createdb --username "$POSTGRES_USER" kanban_testing
fi
