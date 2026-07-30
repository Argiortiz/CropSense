-- Initial CropSense Administrator accounts for Hostinger.
-- Temporary password for all accounts: CropSense@2026
-- Change these passwords after the first successful production login.

INSERT INTO `users` (`fullname`, `username`, `email`, `password`, `role`, `status`)
VALUES
    ('Razell Jay', 'razelljay', 'razelljay@isu.edu.ph', '$2y$10$OtDOH4IH5U9iCuhGbEXu8.BOkmApnGb5llwsBDrl8L5jx3RgLtN4a', 'Administrator', 'Active'),
    ('Gemma Rose E. Maranan', 'gemmarose', 'gemmarose.e.maranan@isu.edu.ph', '$2y$10$OtDOH4IH5U9iCuhGbEXu8.BOkmApnGb5llwsBDrl8L5jx3RgLtN4a', 'Administrator', 'Active'),
    ('Shiela Mae D. Carino', 'shielamae', 'shielamae.d.carino@isu.edu.ph', '$2y$10$OtDOH4IH5U9iCuhGbEXu8.BOkmApnGb5llwsBDrl8L5jx3RgLtN4a', 'Administrator', 'Active')
ON DUPLICATE KEY UPDATE
    `fullname` = VALUES(`fullname`),
    `password` = VALUES(`password`),
    `role` = 'Administrator',
    `status` = 'Active';

