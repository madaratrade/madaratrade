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
<<<<<<< HEAD
<<<<<<< HEAD
=======
=======
>>>>>>> 9e1b178 (Update / fix)

```bash
ALTER TABLE follows
ADD UNIQUE KEY unique_follow_relation (
    follower_id,
    following_id
);
```

```bash
ALTER TABLE follows
ADD INDEX idx_follower_id (follower_id),
ADD INDEX idx_following_id (following_id);
```
<<<<<<< HEAD
>>>>>>> d24f06f (Update / fix)
=======
>>>>>>> 9e1b178 (Update / fix)
