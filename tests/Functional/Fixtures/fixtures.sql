INSERT INTO `warehouse-test`.types (id, type_name) VALUES (1, 'type1');
INSERT INTO `warehouse-test`.companies (id, company_name) VALUES (1, 'company1');
INSERT INTO `warehouse-test`.users (id, email, password, name, surname, phone_number, company_id, salt) VALUES (11, 'hidan9834@mail.ru', '7a97005d27389f05e20280fab6a8d4c8', 'Sasha', 'Usanin', '79125947072', 1, '5b9d2755b9621');
INSERT INTO `warehouse-test`.users (id, email, password, name, surname, phone_number, company_id, salt) VALUES (12, 'hidan98@mail.ru', '84b81b5b3b6f7f9eb1f9603a53b66232', 'Alexandr', 'Usanin', '+79125947072', 1, '5b9d415467b03');
INSERT INTO `warehouse-test`.products (id, name, price, size, type_id, user_owner_id) VALUES (1, 'product1', 10.2, 3, 1, 12);
INSERT INTO `warehouse-test`.products (id, name, price, size, type_id, user_owner_id) VALUES (2, 'product2', 15, 5.1, 1, 12);
INSERT INTO `warehouse-test`.products (id, name, price, size, type_id, user_owner_id) VALUES (3, 'product3', 0.5, 1, 1, 12);
INSERT INTO `warehouse-test`.warehouses (id, address, capacity, user_id, balance, total_size) VALUES (17, 'add1', 200, 11, 0, 0);
INSERT INTO `warehouse-test`.warehouses (id, address, capacity, user_id, balance, total_size) VALUES (19, 'add2', 0, 11, 0, 0);
INSERT INTO `warehouse-test`.warehouses (id, address, capacity, user_id, balance, total_size) VALUES (20, 'add3', 0, 11, 0, 0);
INSERT INTO `warehouse-test`.warehouses (id, address, capacity, user_id, balance, total_size) VALUES (21, 'a', 0, 11, 0, 0);
INSERT INTO `warehouse-test`.warehouses (id, address, capacity, user_id, balance, total_size) VALUES (22, 'b', 0, 11, 0, 0);
INSERT INTO `warehouse-test`.warehouses (id, address, capacity, user_id, balance, total_size) VALUES (23, 'add7', 50, 12, 0, 0);
INSERT INTO `warehouse-test`.warehouses (id, address, capacity, user_id, balance, total_size) VALUES (24, 'add5', 150, 12, 48.4, 62);
INSERT INTO `warehouse-test`.warehouses (id, address, capacity, user_id, balance, total_size) VALUES (26, 'add6', 50, 12, 2, 4);
INSERT INTO `warehouse-test`.products_on_warehouse (product_id, warehouse_id, count) VALUES (1, 24, 2);
INSERT INTO `warehouse-test`.products_on_warehouse (product_id, warehouse_id, count) VALUES (3, 24, 56);
INSERT INTO `warehouse-test`.products_on_warehouse (product_id, warehouse_id, count) VALUES (3, 26, 4);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (1, null, 23, 'app', '2018-09-15 17:33:32', 66);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (2, null, 23, 'app', '2018-09-15 17:34:29', 66);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (4, 23, null, 'detach', '2018-09-15 17:36:39', 121.8);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (7, 23, 24, 'move', '2018-09-15 17:38:10', 10.2);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (8, null, 24, 'app', '2018-09-15 17:38:39', 10.2);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (9, null, 24, 'app', '2018-09-15 17:38:46', 7.5);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (10, null, 24, 'app', '2018-09-15 17:38:48', 7.5);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (11, null, 24, 'app', '2018-09-15 17:38:49', 7.5);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (12, null, 24, 'app', '2018-09-15 17:38:50', 7.5);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (18, 24, 26, 'move', '2018-09-26 15:15:11', 0.5);
INSERT INTO `warehouse-test`.transactions (id, warehouse_from_id, warehouse_to_id, movement_type, date, total_count) VALUES (19, 24, 26, 'move', '2018-09-26 15:20:00', 1);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (1, 1, 5, 51);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (1, 2, 1, 15);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (2, 1, 5, 51);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (2, 2, 1, 15);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (4, 1, 9, 91.8);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (4, 2, 2, 30);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (7, 1, 1, 10.2);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (8, 1, 1, 10.2);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (9, 3, 15, 7.5);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (10, 3, 15, 7.5);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (11, 3, 15, 7.5);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (12, 3, 15, 7.5);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (18, 3, 2, 0.5);
INSERT INTO `warehouse-test`.products_on_transaction (transaction_id, product_id, count, amount) VALUES (19, 3, 2, 1);
