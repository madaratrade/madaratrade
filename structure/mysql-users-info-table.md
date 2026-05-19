```bash
CREATE TABLE `users_info` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT NOT NULL UNIQUE,
  `profile_picture` VARCHAR(255) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `links` TEXT DEFAULT NULL,
  CONSTRAINT `fk_user_info` FOREIGN KEY (`user_id`) REFERENCES `users_account`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
