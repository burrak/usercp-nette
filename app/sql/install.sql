ALTER TABLE auth.account ADD credits INT(11);

CREATE TABLE characters.trade (
id INT(11) NOT NULL AUTO_INCREMENT,
acc1 INT(11),
acc2 INT(11),
char1 INT(11),
char2 INT(11),
status INT(11),
PRIMARY KEY (id)
);
