\c task_manager

CREATE TYPE task_status AS ENUM ('Pending', 'In Progress', 'Completed');

CREATE TABLE tasks (
                       id SERIAL PRIMARY KEY,
                       name TEXT NOT NULL,
                       status task_status NOT NULL DEFAULT 'Pending',
                       creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
