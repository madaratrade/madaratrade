```bash
CREATE TABLE `user_subscriptions` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT NOT NULL,
    `is_paid` BOOLEAN DEFAULT FALSE,
    `payment_date` TIMESTAMP NULL,
    `payment_expire` TIMESTAMP NULL,
    `plan_type` ENUM('monthly', 'yearly', 'lifetime') DEFAULT 'monthly',
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_user_sub` FOREIGN KEY (`user_id`) REFERENCES `users_account`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
