CREATE TABLE file_cache (id CHAR(36) NOT NULL --(DC2Type:uuid)
, "key" VARCHAR(255) NOT NULL, path VARCHAR(512) NOT NULL, hash VARCHAR(64) DEFAULT NULL, size INTEGER NOT NULL, expiresAt DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
, createdAt DATETIME NOT NULL --(DC2Type:datetime_immutable)
, updatedAt DATETIME NOT NULL --(DC2Type:datetime_immutable)
, PRIMARY KEY(id));

CREATE UNIQUE INDEX UNIQ_EBC4DBD08A90ABA9 ON file_cache ("key");

CREATE TABLE groups (id CHAR(36) NOT NULL --(DC2Type:uuid)
, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL --(DC2Type:datetime_immutable)
, discr VARCHAR(255) NOT NULL, PRIMARY KEY(id));

CREATE TABLE persistent_singleton (id CHAR(36) NOT NULL --(DC2Type:uuid)
, "key" VARCHAR(255) NOT NULL, value CLOB NOT NULL, createdAt DATETIME NOT NULL --(DC2Type:datetime_immutable)
, updatedAt DATETIME NOT NULL --(DC2Type:datetime_immutable)
, PRIMARY KEY(id));

CREATE UNIQUE INDEX UNIQ_395FB22E8A90ABA9 ON persistent_singleton ("key");

CREATE TABLE roles (id CHAR(36) NOT NULL --(DC2Type:uuid)
, name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, discr VARCHAR(255) NOT NULL, PRIMARY KEY(id));

CREATE UNIQUE INDEX UNIQ_B63E2EC75E237E06 ON roles (name);

CREATE TABLE users (id CHAR(36) NOT NULL --(DC2Type:uuid)
, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL --(DC2Type:datetime_immutable)
, updatedAt DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
, PRIMARY KEY(id));

CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email);

CREATE TABLE user_groups (id CHAR(36) NOT NULL --(DC2Type:uuid)
, user_id CHAR(36) NOT NULL --(DC2Type:uuid)
, group_id CHAR(36) NOT NULL --(DC2Type:uuid)
, grantedAt DATETIME NOT NULL --(DC2Type:datetime_immutable)
, expiresAt DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
, PRIMARY KEY(id), CONSTRAINT FK_953F224DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_953F224DFE54D947 FOREIGN KEY (group_id) REFERENCES groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE);

CREATE INDEX IDX_953F224DA76ED395 ON user_groups (user_id);

CREATE INDEX IDX_953F224DFE54D947 ON user_groups (group_id);

CREATE UNIQUE INDEX user_group_unique ON user_groups (user_id, group_id);

CREATE TABLE user_roles (id CHAR(36) NOT NULL --(DC2Type:uuid)
, user_id CHAR(36) NOT NULL --(DC2Type:uuid)
, role_id CHAR(36) NOT NULL --(DC2Type:uuid)
, grantedAt DATETIME NOT NULL --(DC2Type:datetime_immutable)
, expiresAt DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
, PRIMARY KEY(id), CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES roles (id) NOT DEFERRABLE INITIALLY IMMEDIATE);

CREATE INDEX IDX_54FCD59FA76ED395 ON user_roles (user_id);

CREATE INDEX IDX_54FCD59FD60322AC ON user_roles (role_id);

CREATE UNIQUE INDEX user_role_unique ON user_roles (user_id, role_id);