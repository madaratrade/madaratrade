```bash
CREATE TABLE `follows` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `follower_id` BIGINT NOT NULL, -- کسی که فالو می‌کند
    `following_id` BIGINT NOT NULL, -- کسی که فالو شده
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_follow` (`follower_id`, `following_id`),
    CONSTRAINT `fk_follower` FOREIGN KEY (`follower_id`) REFERENCES `users_account`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_following` FOREIGN KEY (`following_id`) REFERENCES `users_account`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
