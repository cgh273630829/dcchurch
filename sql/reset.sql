-- ALTER TABLE members DROP FOREIGN KEY fkey_members_stores_id;
-- ALTER TABLE trade_records DROP FOREIGN KEY fkey_trade_records_members_id;
-- ALTER TABLE trade_records DROP FOREIGN KEY fkey_trade_records_stores_id;

-- 清空 trade_records 表
TRUNCATE TABLE trade_records;

-- 清空 stores 表
TRUNCATE TABLE stores;

-- 清空 members 表
TRUNCATE TABLE members;

-- 插入 stores 
INSERT INTO `stores` (`id`, `name`, `store_id`) VALUES
(1, '服務台', 'FDEA181849D76365AB9DB10990948063'),
(2, '多歲的店', 'A1A3FB7A4DC1302F8A2055D132669ED7'),
(3, '美魔女的店', '585618B9C4349A66094F207C81861704'),
(4, '咕嚕咕嚕與叮叮', 'FC9A6CF2E2A3FA976AC3B89528F953FC'),
(5, '喜樂屋', '1BBB11992F83F3AC2183EEEA8DFC69B4'),
(6, '畫糖', 'ECF83B5A89C55C51A4F616936EFE5FFE'),
(7, '好呷好呷綠豆糕', '4E6E4A10A8B1D35B62B778BF0E931563'),
(8, '老王賣刮', '796E20D14405A0249971FE30CADED016'),
(9, '小搞搞蛋餅', 'F030522A12B3EE18457E9FBBB8FAF714'),
(10, '小潘潘-炸片屋', 'C247686C322B6C52A21B9F34FC90E0BE'),
(11, '小老闆的店-紅豆湯/紅茶', 'C3EBEA8960A7981BCF8F11663C7278C9'),
(12, '小老闆的店-壽司', 'E62305B2B01D4971419083AE9B6D2488'),
(13, '小老闆的店-小餅乾', '55A8E583450478A19382179318D91C44'),
(14, '小老闆的店-氣球', '3EF085E57AAFA7639B15A4119E47DB64');

ALTER TABLE `stores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

-- INSERT INTO `members` (`id`, `name`, `user_id`, `store`) VALUES
-- (1, '莊佳宏', 'U13632791ba70a0146ad6f2d13ba60d7e', NULL);

-- ALTER TABLE `members`
--   MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- 設定外鍵
-- ALTER TABLE `members`
--   ADD CONSTRAINT `fkey_members_stores_id` FOREIGN KEY (`store`) REFERENCES `stores` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

-- ALTER TABLE `trade_records`
--   ADD CONSTRAINT `fkey_trade_records_members_id` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
--   ADD CONSTRAINT `fkey_trade_records_stores_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;