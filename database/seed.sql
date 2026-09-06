-- Seed data: a couple of default rooms so rooms.php isn't empty on first run.
INSERT IGNORE INTO rooms (name, slug) VALUES
    ('General', 'general'),
    ('Off-topic', 'off-topic');
