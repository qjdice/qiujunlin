create table if not exists `listurl`(
    `id` int not null auto_increment,
    primary key(`id`),
    `listurl` varchar(32) not null
)engine=innodb default charset=utf8;


create table if not exists `imgconurl`(
    `id` int not null auto_increment,
    primary key(`id`),
    `conurl` varchar(32) not null,
    `title` varchar(32) not null default 'null'
)engine=innodb default charset=utf8;
