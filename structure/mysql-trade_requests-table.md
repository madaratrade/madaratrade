```bash
CREATE TABLE `trade_requests` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `sender_id` BIGINT NOT NULL,
    `receiver_id` BIGINT NOT NULL,
    `status` ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
    `message` TEXT NULL, -- پیامی که موقع ترید درخواست میدن
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_trade_sender` FOREIGN KEY (`sender_id`) REFERENCES `users_account`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_trade_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users_account`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
<<<<<<< HEAD
<<<<<<< HEAD
```
=======
```
>>>>>>> d24f06f (Update / fix)
=======
```
>>>>>>> 9e1b178 (Update / fix)
