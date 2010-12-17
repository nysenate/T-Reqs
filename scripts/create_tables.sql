
create table request (
  id integer auto_increment primary key,
  uuid varchar(32) not null unique,
  retries integer default 0,
  requester varchar(32) not null references user (username),
  reviewer varchar(32) references user (username),
  session_id varchar(36) not null,
  account_id varchar(36) not null,
  message_id varchar(36) not null,
  account_name varchar(64),
  message_name varchar(64),
  delivery_date date,
  district integer references senator (district),
  from_addr varchar(64),
  from_name varchar(64),
  reply_addr varchar(64),
  cc_user tinyint,
  cc_email varchar(64),
  created_on datetime,
  updated_on datetime,
  reviewed_on datetime,
  closed_on datetime,
  status enum('INCOMPLETE','AWAITING_REVIEW','UNDER_REVIEW','APPROVED','REJECTED') not null default 'INCOMPLETE',
  request_notes varchar(2048),
  review_notes varchar(2048),
  index username_idx (requester),
  index session_idx (session_id)
);


create table request_history (
  req_id integer references request (id) on delete cascade,
  username varchar(32) not null references user (username),
  created_on datetime,
  role enum('REQUESTER','REVIEWER') not null,
  action enum('INIT_REQUEST','SENT_REQUEST','RESENT_REQUEST','CANCEL_REQUEST','INIT_REVIEW','APPROVE_REQUEST','REJECT_REQUEST'),
  notes varchar(2048)
);
  
  
create table request_list (
  req_id integer references request (id) on delete cascade,
  list_id varchar(36),
  list_name varchar(64),
  primary key (req_id, list_id)
);


create table request_segment (
  req_id integer references request (id) on delete cascade,
  seg_id varchar(36),
  seg_name varchar(64),
  primary key (req_id, seg_id)
);


create table user (
  username varchar(32) primary key,
  password varchar(64),
  firstname varchar(32),
  lastname varchar(32),
  scope enum('BRONTO','LOCAL','LDAP') not null default 'BRONTO',
  role enum('REQUESTER','REVIEWER') not null default 'REQUESTER',
  sitename varchar(64),
  phone varchar(32),
  email varchar(64),
  created_on datetime,
  updated_on datetime,
  last_login datetime
);


create table session (
  id varchar(36) primary key,
  username varchar(32),
  account_id varchar(36),
  created_on datetime,
  updated_on datetime
);


create table senator (
  district integer primary key,
  firstname varchar(32),
  lastname varchar(32)
);



insert into user values ('reviewer1',NULL,'Reviewer','Default','LOCAL','REVIEWER','newyorksenateagency','518-455-2011','blastemailrequest@gmail.com',NOW(),NOW(),NULL);

insert into user values ('kz','nyss2009','Ken','Zalewski','LOCAL','REVIEWER','newyorksenateagency','518-455-2912','blastemailrequest@gmail.com',NOW(),NOW(),NULL);

insert into user values ('brenner','changeme','Krista','Brenner','LDAP','REVIEWER','newyorksenateagency','518-455-5522','brenner@senate.state.ny.us',NOW(),NOW(),NULL);

insert into user values ('hotaling','changeme','Scott','Hotaling','LDAP','REVIEWER','newyorksenateagency','518-455-6603','hotaling@senate.state.ny.us',NOW(),NOW(),NULL);

insert into user values ('klucas','changeme','Kelly','Lucas','LDAP','REVIEWER','newyorksenateagency','518-455-6615','klucas@senate.state.ny.us',NOW(),NOW(),NULL);

insert into user values ('mergian','changeme','Gwen','Mergian','LDAP','REVIEWER','newyorksenateagency','518-455-6644','mergian@senate.state.ny.us',NOW(),NOW(),NULL);

insert into user values ('oechsner','changeme','Rebecca','Oechsner','LDAP','REVIEWER','newyorksenateagency','518-455-6644','oechsner@senate.state.ny.us',NOW(),NOW(),NULL);

insert into user values ('plath','changeme','Gail','Plath','LDAP','REVIEWER','newyorksenateagency','518-455-6626','plath@senate.state.ny.us',NOW(),NOW(),NULL);

insert into user values ('shaw','changeme','Ann','Shaw-Better','LDAP','REVIEWER','newyorksenateagency','518-455-6675','shaw@senate.state.ny.us',NOW(),NOW(),NULL);

insert into user values ('varno','changeme','Andy','Varno','LDAP','REVIEWER','newyorksenateagency','518-455-6600','varno@senate.state.ny.us',NOW(),NOW(),NULL);

insert into user values ('zalewski','changeme','Ken','Zalewski','LDAP','REVIEWER','newyorksenateagency','518-455-2912','zalewski@senate.state.ny.us',NOW(),NOW(),NULL);




insert into senator values (20,'Eric','Adams');
insert into senator values (15,'Joseph','Addabbo');
insert into senator values (55,'James','Alesi');
insert into senator values (48,'Darrel','Aubertine');
insert into senator values (42,'John','Bonacic');
insert into senator values (46,'Neil','Breslin');
insert into senator values (50,'John','DeFrancisco');
insert into senator values (32,'Ruben','Diaz');
insert into senator values (17,'Martin','Dilan');
insert into senator values (29,'Thomas','Duane');
insert into senator values (33,'Pedro','Espada');
insert into senator values (44,'Hugh','Farley');
insert into senator values (2,'John','Flanagan');
insert into senator values (3,'Brian','Foley');
insert into senator values (8,'Charles','Fuschillo');
insert into senator values (22,'Martin','Golden');
insert into senator values (47,'Joseph','Griffo');
insert into senator values (6,'Kemp','Hannon');
insert into senator values (36,'Ruth','Hassell-Thompson');
insert into senator values (10,'Shirley','Huntley');
insert into senator values (7,'Craig','Johnson');
insert into senator values (4,'Owen','Johnson');
insert into senator values (34,'Jeffrey','Klein');
insert into senator values (26,'Liz','Krueger');
insert into senator values (27,'Carl','Kruger');
insert into senator values (24,'Andrew','Lanza');
insert into senator values (39,'William','Larkin');
insert into senator values (1,'Kenneth','LaValle');
insert into senator values (40,'Vincent','Leibell');
insert into senator values (52,'Tom','Libous');
insert into senator values (45,'Elizabeth','Little');
insert into senator values (5,'Carl','Marcellino');
insert into senator values (62,'George','Maziarz');
insert into senator values (43,'Roy','McDonald');
insert into senator values (18,'Velmanette','Montgomery');
insert into senator values (38,'Thomas','Morahan');
insert into senator values (54,'Michael','Nozzolio');
insert into senator values (12,'George','Onorato');
insert into senator values (37,'Suzi','Oppenheimer');
insert into senator values (11,'Frank','Padavan');
insert into senator values (21,'Kevin','Parker');
insert into senator values (13,'Jose','Peralta');
insert into senator values (30,'Bill','Perkins');
insert into senator values (61,'Michael','Ranzenhofer');
insert into senator values (56,'Joseph','Robach');
insert into senator values (41,'Stephen','Saland');
insert into senator values (19,'John','Sampson');
insert into senator values (23,'Diane','Savino');
insert into senator values (31,'Eric','Schneiderman');
insert into senator values (28,'Jose','Serrano');
insert into senator values (51,'James','Seward');
insert into senator values (9,'Dean','Skelos');
insert into senator values (14,'Malcolm','Smith');
insert into senator values (25,'Daniel','Squadron');
insert into senator values (58,'William','Stachowski');
insert into senator values (16,'Toby Ann','Stavisky');
insert into senator values (35,'Andrea','Stewart-Cousins');
insert into senator values (60,'Antoine','Thompson');
insert into senator values (49,'David','Valesky');
insert into senator values (59,'Dale','Volker');
insert into senator values (53,'George','Winner');
insert into senator values (57,'Catharine','Young');

