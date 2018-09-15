create table companies
(
  id           int auto_increment,
  company_name varchar(255) not null,
  constraint companies_company_name_uindex
  unique (company_name),
  constraint companies_id_uindex
  unique (id)
);

alter table companies
  add primary key (id);

create table types
(
  id        int auto_increment,
  type_name varchar(255) not null,
  constraint types_id_uindex
  unique (id),
  constraint types_type_name_uindex
  unique (type_name)
);

alter table types
  add primary key (id);

create table users
(
  id           int auto_increment,
  email        varchar(255) not null,
  password     varchar(45)  not null,
  name         varchar(45)  not null,
  surname      varchar(255) not null,
  phone_number varchar(45)  not null,
  company_id   int          null,
  salt         varchar(45)  null,
  constraint users_email_uindex
  unique (email),
  constraint users_id_uindex
  unique (id),
  constraint users_companies_id_fk
  foreign key (company_id) references companies (id)
);

alter table users
  add primary key (id);

create table products
(
  id            int auto_increment,
  name          varchar(255) not null,
  price         double       not null,
  size          double       not null,
  type_id       int          null,
  user_owner_id int          null,
  constraint products_id_uindex
  unique (id),
  constraint products_types_id_fk
  foreign key (type_id) references types (id)
    on delete set null,
  constraint products_users_id_fk
  foreign key (user_owner_id) references users (id)
    on delete cascade
);

alter table products
  add primary key (id);

create table warehouse
(
  id      int auto_increment
    primary key,
  name    varchar(45)  not null,
  address varchar(255) not null
);

create table warehouses
(
  id         int auto_increment,
  address    varchar(255)       not null,
  capacity   double             not null,
  user_id    int                null,
  balance    double default '0' not null,
  total_size double default '0' null,
  constraint warehouses_address_uindex
  unique (address),
  constraint warehouses_id_uindex
  unique (id),
  constraint warehouses_users_id_fk
  foreign key (user_id) references users (id)
    on delete cascade
);

alter table warehouses
  add primary key (id);

create table products_on_warehouse
(
  product_id   int not null,
  warehouse_id int not null,
  count        int not null,
  primary key (product_id, warehouse_id),
  constraint products_on_warehouse_products_id_fk
  foreign key (product_id) references products (id)
    on delete cascade,
  constraint products_on_warehouse_warehouses_id_fk
  foreign key (warehouse_id) references warehouses (id)
    on delete cascade
);

create trigger warehouse_balance_counter
  after INSERT
  on products_on_warehouse
  for each row
  BEGIN
    DECLARE `@balance` double;
    SET `@balance` = (SELECT SUM(count * (SELECT price FROM products WHERE id = product_id))
                      FROM products_on_warehouse
                      WHERE warehouse_id = NEW.warehouse_id);
    if `@balance` IS NULL
    THEN
      SET `@balance` = 0;
    end if;
    UPDATE warehouses SET balance = `@balance` WHERE id = NEW.warehouse_id;
  END;

create trigger warehouse_balance_counter_delete
  after DELETE
  on products_on_warehouse
  for each row
  BEGIN
    DECLARE `@balance` double;
    SET `@balance` = (SELECT SUM(count * (SELECT price FROM products WHERE id = product_id))
                      FROM products_on_warehouse
                      WHERE warehouse_id = OLD.warehouse_id);
    if `@balance` IS NULL
    THEN
      SET `@balance` = 0;
    end if;
    UPDATE warehouses SET balance = `@balance` WHERE id = OLD.warehouse_id;
  END;

create trigger warehouse_balance_counter_update
  after UPDATE
  on products_on_warehouse
  for each row
  BEGIN
    DECLARE `@balance` double;
    SET `@balance` = (SELECT SUM(count * (SELECT price FROM products WHERE id = product_id))
                      FROM products_on_warehouse
                      WHERE warehouse_id = NEW.warehouse_id);
    if `@balance` IS NULL
    THEN
      SET `@balance` = 0;
    end if;
    UPDATE warehouses SET balance = `@balance` WHERE id = NEW.warehouse_id;
  END;

create trigger warehouse_capacity_counter
  after INSERT
  on products_on_warehouse
  for each row
  BEGIN
    DECLARE `@size` double;
    SET `@size` = (SELECT SUM(count * (SELECT size FROM products WHERE id = product_id))
                   FROM products_on_warehouse
                   WHERE warehouse_id = NEW.warehouse_id);
    if `@size` IS NULL
    THEN
      SET `@size` = 0;
    end if;
    UPDATE warehouses SET total_size = `@size` WHERE id = NEW.warehouse_id;
  end;

create trigger warehouse_capacity_counter_delete
  after DELETE
  on products_on_warehouse
  for each row
  BEGIN
    DECLARE `@size` double;
    SET `@size` = (SELECT SUM(count * (SELECT size FROM products WHERE id = product_id))
                   FROM products_on_warehouse
                   WHERE warehouse_id = OLD.warehouse_id);
    if `@size` IS NULL
    THEN
      SET `@size` = 0;
    end if;
    UPDATE warehouses SET total_size = `@size` WHERE id = OLD.warehouse_id;
  end;

create trigger warehouse_capacity_counter_update
  after UPDATE
  on products_on_warehouse
  for each row
  BEGIN
    DECLARE `@size` double;
    SET `@size` = (SELECT SUM(count * (SELECT size FROM products WHERE id = product_id))
                   FROM products_on_warehouse
                   WHERE warehouse_id = NEW.warehouse_id);
    if `@size` IS NULL
    THEN
      SET `@size` = 0;
    end if;
    UPDATE warehouses SET total_size = `@size` WHERE id = NEW.warehouse_id;
  end;

create table transactions
(
  id                int auto_increment,
  warehouse_from_id int                                null,
  warehouse_to_id   int                                null,
  movement_type     varchar(45)                        not null,
  date              datetime default CURRENT_TIMESTAMP not null,
  total_count       double default '0'                 null,
  constraint products_transactions_id_uindex
  unique (id),
  constraint products_movements_warehouses_id_fk
  foreign key (warehouse_from_id) references warehouses (id)
    on delete cascade,
  constraint products_movements_warehouses_id_fk_2
  foreign key (warehouse_to_id) references warehouses (id)
    on delete cascade
);

alter table transactions
  add primary key (id);

create table products_on_transaction
(
  transaction_id int                not null,
  product_id     int                not null,
  count          int                not null,
  amount         double default '0' not null,
  primary key (transaction_id, product_id),
  constraint products_on_transaction_products_id_fk
  foreign key (product_id) references products (id)
    on delete cascade,
  constraint products_on_transaction_products_transactions_id_fk
  foreign key (transaction_id) references transactions (id)
    on delete cascade
);

create trigger transaction_amount_counter
  before INSERT
  on products_on_transaction
  for each row
  BEGIN
    SET NEW.amount = (NEW.count) * (SELECT price FROM products WHERE id = NEW.product_id);
  END;

create trigger transaction_total_counter
  after INSERT
  on products_on_transaction
  for each row
  BEGIN
    DECLARE `@total_count` double;
    SET `@total_count` = (SELECT SUM(amount) FROM products_on_transaction WHERE transaction_id = NEW.transaction_id);
    UPDATE transactions SET total_count = `@total_count` WHERE id = NEW.transaction_id;
  END;


