DROP TABLE IF EXISTS %TABLE_PREFIX%config;
CREATE TABLE %TABLE_PREFIX%config (
  id tinyint(1) unsigned NOT NULL auto_increment,
  isonline tinyint(1) unsigned NOT NULL default '0',
  timezone_offset float(3,1) NOT NULL default '0.0',
  enable_daylight_saving tinyint(1) unsigned NOT NULL default '0',
  staff_session_timeout int(10) unsigned NOT NULL default '30',
  client_session_timeout int(10) unsigned NOT NULL default '30',
  max_page_size tinyint(3) unsigned NOT NULL default '25',
  max_open_tickets tinyint(3) unsigned NOT NULL default '0',
  max_file_size int(11) unsigned NOT NULL default '1048576',
  autolock_minutes tinyint(3) unsigned NOT NULL default '3',
  overdue_grace_period int(10) unsigned NOT NULL default '0',
  default_email tinyint(4) unsigned NOT NULL default '0',
  default_dept tinyint(3) unsigned NOT NULL default '0',
  default_priority tinyint(2) unsigned NOT NULL default '2',
  default_template tinyint(4) unsigned NOT NULL default '1',
  clickable_urls tinyint(1) unsigned NOT NULL default '1',
  allow_priority_change tinyint(1) unsigned NOT NULL default '0',
  use_email_priority tinyint(1) unsigned NOT NULL default '0',
  enable_auto_cron tinyint(1) unsigned NOT NULL default '0',
  enable_pop3_fetch tinyint(1) unsigned NOT NULL default '0',
  enable_email_piping tinyint(1) unsigned NOT NULL default '0',
  send_sql_errors tinyint(1) unsigned NOT NULL default '1',
  send_mailparse_errors tinyint(1) unsigned NOT NULL default '1',
  send_login_errors tinyint(1) unsigned NOT NULL default '1',
  save_email_headers tinyint(1) unsigned NOT NULL default '1',
  strip_quoted_reply tinyint(1) unsigned NOT NULL default '1',
  ticket_autoresponder tinyint(1) unsigned NOT NULL default '0',
  message_autoresponder tinyint(1) unsigned NOT NULL default '0',
  ticket_alert_active tinyint(1) unsigned NOT NULL default '0',
  ticket_alert_admin tinyint(1) unsigned NOT NULL default '1',
  ticket_alert_dept_manager tinyint(1) unsigned NOT NULL default '1',
  ticket_alert_dept_members tinyint(1) unsigned NOT NULL default '0',
  message_alert_active tinyint(1) unsigned NOT NULL default '0',
  message_alert_laststaff tinyint(1) unsigned NOT NULL default '1',
  message_alert_assigned tinyint(1) unsigned NOT NULL default '1',
  message_alert_dept_manager tinyint(1) unsigned NOT NULL default '0',
  overdue_alert_active tinyint(1) unsigned NOT NULL default '0',
  overdue_alert_assigned tinyint(1) unsigned NOT NULL default '1',
  overdue_alert_dept_manager tinyint(1) unsigned NOT NULL default '1',
  overdue_alert_dept_members tinyint(1) unsigned NOT NULL default '0',
  auto_assign_reopened_tickets tinyint(1) unsigned NOT NULL default '0',
  show_assigned_tickets tinyint(1) unsigned NOT NULL default '0',
  overlimit_notice_active tinyint(1) unsigned NOT NULL default '0',
  email_attachments tinyint(1) unsigned NOT NULL default '1',
  allow_attachments tinyint(1) unsigned NOT NULL default '0',
  allow_email_attachments tinyint(1) unsigned NOT NULL default '0',
  allow_online_attachments tinyint(1) unsigned NOT NULL default '0',
  allow_online_attachments_onlogin tinyint(1) unsigned NOT NULL default '0',
  random_ticket_ids tinyint(1) unsigned NOT NULL default '1',
  upload_dir varchar(255) NOT NULL default '',
  allowed_filetypes varchar(255) NOT NULL default '.doc, .pdf',
  time_format varchar(32) NOT NULL default ' h:i A',
  date_format varchar(32) NOT NULL default 'm/d/Y',
  datetime_format varchar(60) NOT NULL default 'm/d/Y g:i a',
  daydatetime_format varchar(60) NOT NULL default 'D, M j Y g:ia',
  reply_separator varchar(60) NOT NULL default ' -- do not edit --',
  noreply_email varchar(125) NOT NULL default '',
  alert_email varchar(125) NOT NULL default '',
  admin_email varchar(125) NOT NULL default '',
  helpdesk_title varchar(255) NOT NULL default 'osTicket Support Ticket System',
  helpdesk_url varchar(255) NOT NULL default '',
  api_whitelist tinytext NULL,
  api_key varchar(125) NOT NULL default '',
  ostversion varchar(16) NOT NULL default '',
  updated timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY isoffline (isonline)
) ENGINE=MyISAM;

INSERT INTO %TABLE_PREFIX%config SET isonline=1, updated=NOW();

DROP TABLE IF EXISTS %TABLE_PREFIX%department;
CREATE TABLE %TABLE_PREFIX%department (
  dept_id int(11) unsigned NOT NULL auto_increment,
  email_id int(10) unsigned NOT NULL default '0',
  manager_id int(10) unsigned NOT NULL default '0',
  dept_name varchar(32) NOT NULL default '',
  dept_signature varchar(255) NOT NULL default '',
  ispublic tinyint(1) unsigned NOT NULL default '1',
  noreply_autoresp tinyint(1) unsigned NOT NULL default '1',
  ticket_auto_response tinyint(1) NOT NULL default '1',
  message_auto_response tinyint(1) NOT NULL default '0',
  can_append_signature tinyint(1) NOT NULL default '1',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (dept_id),
  UNIQUE KEY dept_name (dept_name),
  KEY manager_id (manager_id)
) ENGINE=MyISAM;

INSERT INTO %TABLE_PREFIX%department VALUES (1,1,1,'Support','Support Dept',1,1,1,1,1,NOW(),NOW());

DROP TABLE IF EXISTS %TABLE_PREFIX%email;
CREATE TABLE %TABLE_PREFIX%email (
  email_id int(11) unsigned NOT NULL auto_increment,
  noautoresp tinyint(1) unsigned NOT NULL default '0',
  priority_id tinyint(3) unsigned NOT NULL default '0',
  dept_id tinyint(3) unsigned NOT NULL default '0',
  email varchar(125) NOT NULL default '',
  name varchar(32) NOT NULL default '',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (email_id),
  UNIQUE KEY email (email),
  KEY priority_id (priority_id),
  KEY dept_id (dept_id)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS %TABLE_PREFIX%email_banlist;
CREATE TABLE %TABLE_PREFIX%email_banlist (
  id int(11) NOT NULL auto_increment,
  email varchar(255) NOT NULL default '',
  submitter varchar(126) NOT NULL default '',
  added datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  UNIQUE KEY email (email)
) ENGINE=MyISAM;


INSERT INTO %TABLE_PREFIX%email_banlist VALUES (1,'test@example.com','System',NOW());


DROP TABLE IF EXISTS %TABLE_PREFIX%email_pop3;
CREATE TABLE %TABLE_PREFIX%email_pop3 (
  email_id int(10) unsigned NOT NULL default '0',
  popenabled tinyint(1) NOT NULL default '0',
  pophost varchar(125) NOT NULL default '',
  popuser varchar(125) NOT NULL default '',
  poppasswd varchar(125) NOT NULL default '',
  delete_msgs tinyint(1) unsigned NOT NULL default '0',
  fetchfreq int(3) unsigned NOT NULL default '5',
  `errors` tinyint(3) unsigned NOT NULL default '0',
  lasterror datetime NOT NULL default '0000-00-00 00:00:00',
  lastfetch datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (email_id),
  KEY consec_errors (`errors`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS %TABLE_PREFIX%email_template;
CREATE TABLE %TABLE_PREFIX%email_template (
  tpl_id int(11) NOT NULL auto_increment,
  cfg_id int(10) unsigned NOT NULL default '0',
  name varchar(32) NOT NULL default '',
  ticket_autoresp_subj varchar(255) NOT NULL default '',
  ticket_autoresp_body text NOT NULL,
  ticket_alert_subj varchar(255) NOT NULL default '',
  ticket_alert_body text NOT NULL,
  message_autoresp_subj varchar(255) NOT NULL default '',
  message_autoresp_body text NOT NULL,
  message_alert_subj varchar(255) NOT NULL default '',
  message_alert_body text NOT NULL,
  assigned_alert_subj varchar(255) NOT NULL default '',
  assigned_alert_body text NOT NULL,
  ticket_overdue_subj varchar(255) NOT NULL default '',
  ticket_overdue_body text NOT NULL,
  ticket_overlimit_subj varchar(255) NOT NULL default '',
  ticket_overlimit_body text NOT NULL,
  ticket_reply_subj varchar(255) NOT NULL default '',
  ticket_reply_body text NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (tpl_id),
  KEY cfg_id (cfg_id),
  FULLTEXT KEY message_subj (ticket_reply_subj)
) ENGINE=MyISAM;

INSERT INTO %TABLE_PREFIX%email_template VALUES (1,1,'osTicket Default Template','Support Ticket Opened [#%ticket]','%name,\r\n\r\nA request for support has been created and assigned ticket #%ticket. A representative will follow-up with you as soon as possible.\r\n\r\nYou can view this ticket\'s progress online here: %url/view.php?e=%email&t=%ticket.\r\n\r\nIf you wish to send additional comments or information regarding this issue, please don\'t open a new ticket. Simply login using the link above and update the ticket.\r\n\r\n%signature','New Ticket Alert','%staff,\r\n\r\nNew ticket #%ticket created.\r\n-------------------\r\nName: %name\r\nEmail: %email\r\nDept: %dept\r\n\r\n%message\r\n-------------------\r\n\r\nTo view/respond to the ticket, please login to the support ticket system.\r\n\r\nYour friendly,\r\n\r\nCustomer Support  System powered by osTicket.','[#%ticket] Message Added','%name,\r\n\r\nYour reply to support request #%ticket has been noted.\r\n\r\nYou can view this support request progress online here: %url/view.php?e=%email&t=%ticket.\r\n\r\n%signature','New Message Alert','%staff,\r\n\r\nNew message appended to ticket #%ticket\r\n\r\n----------------------\r\nName: %name\r\nEmail: %email\r\nDept: %dept\r\n\r\n%message\r\n-------------------\r\n\r\nTo view/respond to the ticket, please login to the support ticket system.\r\n\r\nYour friendly,\r\n\r\nCustomer Support System - powered by osTicket.','Ticket #%ticket Assigned to you','%assignee,\r\n\r\n%assigner has assigned ticket #%ticket to you!\r\n\r\n%message\r\n\r\nTo view complete details, simply login to the support system.\r\n\r\nYour friendly,\r\n\r\nSupport Ticket System - powered by osTicket.','Stale Ticket Alert','%staff,\r\n\r\nA ticket #%ticket assigned to you or in your department is seriously overdue.\r\n\r\n%url/scp/tickets.php?id=%ticket\r\n\r\nWe should all try to guarantee that all tickets are being addressed in a timely manner. Enough baby talk...please address the issue or you will hear from me again.\r\n\r\n\r\nYour friendly,\r\n\r\nSupport Ticket System - powered by osTicket.','Support Ticket Denied','%name\r\n\r\nNo support ticket created. You\'ve exceeded maximum open tickets allowed.\r\n\r\nThis is a temporary block. To be able to open another ticket, one of your pending tickets must be closed. To update or add comments to an open ticket simply login using the link below.\r\n\r\n%url/view.php?e=%email\r\n\r\nThank you.\r\n\r\nSupport Ticket System','[#%ticket] %subject','%name,\r\n\r\nOur customer support team personnel has replied to your support request #%ticket \r\n\r\n%message\r\n\r\nWe hope this response has sufficiently answered your questions. If not, please do not send another email. Instead, reply to this email or login to your account for a complete archive of all your support request and responses.\r\n\r\n%url/view.php?e=%email&t=%ticket\r\n\r\n%signature',NOW(),NOW());

DROP TABLE IF EXISTS %TABLE_PREFIX%groups;
CREATE TABLE %TABLE_PREFIX%groups (
  group_id int(10) unsigned NOT NULL auto_increment,
  group_enabled tinyint(1) unsigned NOT NULL default '1',
  group_name varchar(50) NOT NULL default '',
  dept_access varchar(255) NOT NULL default '',
  can_delete_tickets tinyint(1) unsigned NOT NULL default '0',
  can_close_tickets tinyint(1) unsigned NOT NULL default '0',
  can_transfer_tickets tinyint(1) NOT NULL default '1',
  can_ban_emails tinyint(1) unsigned NOT NULL default '0',
  can_manage_kb tinyint(1) unsigned NOT NULL default '0',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (group_id),
  KEY group_active (group_enabled)
) ENGINE=MyISAM;

INSERT INTO %TABLE_PREFIX%groups VALUES (1,1,'Admins','1',1,1,1,1,1,NOW(),NOW());
INSERT INTO %TABLE_PREFIX%groups VALUES (2,1,'Managers','1',1,1,1,1,1,NOW(),NOW());
INSERT INTO %TABLE_PREFIX%groups VALUES (3,1,'Staff','1',0,0,0,0,0,NOW(),NOW());

DROP TABLE IF EXISTS %TABLE_PREFIX%help_topic;
CREATE TABLE %TABLE_PREFIX%help_topic (
  topic_id int(11) unsigned NOT NULL auto_increment,
  isactive tinyint(1) unsigned NOT NULL default '1',
  noautoresp tinyint(3) unsigned NOT NULL default '0',
  priority_id tinyint(3) unsigned NOT NULL default '0',
  dept_id tinyint(3) unsigned NOT NULL default '0',
  topic varchar(32) NOT NULL default '',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (topic_id),
  UNIQUE KEY topic (topic),
  KEY priority_id (priority_id),
  KEY dept_id (dept_id)
) ENGINE=MyISAM;


INSERT INTO %TABLE_PREFIX%help_topic VALUES (1,1,0,2,1,'Support',NOW(),NOW());
INSERT INTO %TABLE_PREFIX%help_topic VALUES (2,1,0,3,1,'Billing',NOW(),NOW());


DROP TABLE IF EXISTS %TABLE_PREFIX%kb_premade;
CREATE TABLE %TABLE_PREFIX%kb_premade (
  premade_id int(10) unsigned NOT NULL auto_increment,
  dept_id int(10) unsigned NOT NULL default '0',
  isenabled tinyint(1) unsigned NOT NULL default '1',
  title varchar(125) NOT NULL default '',
  answer text NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
                            
  PRIMARY KEY  (premade_id),
  UNIQUE KEY title_2 (title),
  KEY dept_id (dept_id),
  KEY active (isenabled),
  FULLTEXT KEY title (title,answer)
) ENGINE=MyISAM;

INSERT INTO %TABLE_PREFIX%kb_premade VALUES (1,0,1,'What is osTicket (sample)?','osTicket is a support ticket system.',NOW(),NOW());

DROP TABLE IF EXISTS %TABLE_PREFIX%staff;
CREATE TABLE %TABLE_PREFIX%staff (
  staff_id int(11) unsigned NOT NULL auto_increment,
  group_id int(10) unsigned NOT NULL default '0',
  dept_id int(10) unsigned NOT NULL default '0',
  username varchar(32) NOT NULL default '',
  firstname varchar(32) default NULL,
  lastname varchar(32) default NULL,
  passwd varchar(128) default NULL,
  email varchar(128) default NULL,
  phone varchar(24) NOT NULL default '',
  phone_ext varchar(6) default NULL,
  mobile varchar(24) NOT NULL default '',
  signature varchar(255) NOT NULL default '',
  isactive tinyint(1) NOT NULL default '1',
  isadmin tinyint(1) NOT NULL default '0',
  isvisible tinyint(1) unsigned NOT NULL default '1',
  onvacation tinyint(1) unsigned NOT NULL default '0',
  daylight_saving tinyint(1) unsigned NOT NULL default '0',
  append_signature tinyint(1) unsigned NOT NULL default '0',
  change_passwd tinyint(1) unsigned NOT NULL default '0',
  timezone_offset float(3,1) NOT NULL default '0.0',
  max_page_size int(11) NOT NULL default '0',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  lastlogin datetime default NULL,
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (staff_id),
  UNIQUE KEY username (username),
  KEY dept_id (dept_id),
  KEY issuperuser (isadmin),
  KEY group_id (group_id,staff_id)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS %TABLE_PREFIX%ticket;
CREATE TABLE %TABLE_PREFIX%ticket (
  ticket_id int(11) unsigned NOT NULL auto_increment,
  ticketID int(11) unsigned NOT NULL default '0',
  dept_id int(10) unsigned NOT NULL default '1',
  priority_id int(10) unsigned NOT NULL default '2',
  staff_id int(10) unsigned NOT NULL default '0',
  email varchar(120) NOT NULL default '',
  name varchar(32) NOT NULL default '',
  `subject` varchar(64) NOT NULL default '[no subject]',
  phone varchar(16) default NULL,
  ip_address varchar(16) NOT NULL default '',
  `status` enum('open','closed') NOT NULL default 'open',
  source enum('Web','Email','Phone') default NULL,
  isoverdue tinyint(1) unsigned NOT NULL default '0',
  reopened datetime default NULL,
  closed datetime default NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ticket_id),
  UNIQUE KEY email_extid (ticketID,email),
  KEY dept_id (dept_id),
  KEY staff_id (staff_id),
  KEY `status` (`status`),
  KEY priority_id (priority_id)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS %TABLE_PREFIX%ticket_attachment;
CREATE TABLE %TABLE_PREFIX%ticket_attachment (
  attach_id int(11) unsigned NOT NULL auto_increment,
  ticket_id int(11) unsigned NOT NULL default '0',
  ref_id int(11) unsigned NOT NULL default '0',
  ref_type enum('M','R') NOT NULL default 'M',
  file_size varchar(32) NOT NULL default '',
  file_name varchar(128) NOT NULL default '',
  file_key varchar(128) NOT NULL default '',
  deleted tinyint(1) unsigned NOT NULL default '0',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime default NULL,
  PRIMARY KEY  (attach_id),
  KEY ticket_id (ticket_id),
  KEY ref_type (ref_type),
  KEY ref_id (ref_id)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS %TABLE_PREFIX%ticket_lock;
CREATE TABLE %TABLE_PREFIX%ticket_lock (
  lock_id int(11) unsigned NOT NULL auto_increment,
  ticket_id int(11) unsigned NOT NULL default '0',
  staff_id int(10) unsigned NOT NULL default '0',
  expire datetime default NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (lock_id),
  UNIQUE KEY ticket_id (ticket_id),
  KEY staff_id (staff_id)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS %TABLE_PREFIX%ticket_message;
CREATE TABLE %TABLE_PREFIX%ticket_message (
  msg_id int(11) unsigned NOT NULL auto_increment,
  ticket_id int(11) unsigned NOT NULL default '0',
  message text NOT NULL,
  headers text,
  source varchar(16) default NULL,
  ip_address varchar(16) default NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime default NULL,
  PRIMARY KEY  (msg_id),
  KEY ticket_id (ticket_id)
) ENGINE=MyISAM;



DROP TABLE IF EXISTS %TABLE_PREFIX%ticket_note;
CREATE TABLE %TABLE_PREFIX%ticket_note (
  note_id int(11) unsigned NOT NULL auto_increment,
  ticket_id int(11) unsigned NOT NULL default '0',
  staff_id int(10) unsigned NOT NULL default '0',
  source varchar(32) NOT NULL default '',
  title varchar(255) NOT NULL default 'Generic Intermal Notes',
  note text NOT NULL,
  created datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (note_id),
  KEY ticket_id (ticket_id),
  KEY staff_id (staff_id)
) ENGINE=MyISAM;



DROP TABLE IF EXISTS %TABLE_PREFIX%ticket_priority;
CREATE TABLE %TABLE_PREFIX%ticket_priority (
  priority_id tinyint(4) NOT NULL auto_increment,
  priority varchar(60) NOT NULL default '',
  priority_desc varchar(30) NOT NULL default '',
  priority_color varchar(7) NOT NULL default '',
  priority_urgency tinyint(1) unsigned NOT NULL default '0',
  ispublic tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (priority_id),
  UNIQUE KEY priority (priority),
  KEY priority_urgency (priority_urgency),
  KEY ispublic (ispublic)
) ENGINE=MyISAM;



INSERT INTO %TABLE_PREFIX%ticket_priority VALUES (1,'low','Low','#DDFFDD',4,1);
INSERT INTO %TABLE_PREFIX%ticket_priority VALUES (2,'normal','Normal','#FFFFF0',3,1);
INSERT INTO %TABLE_PREFIX%ticket_priority VALUES (3,'high','High','#FEE7E7',2,1);
INSERT INTO %TABLE_PREFIX%ticket_priority VALUES (4,'emergency','Emergency','#FEE7E7',1,0);


DROP TABLE IF EXISTS %TABLE_PREFIX%ticket_response;
CREATE TABLE %TABLE_PREFIX%ticket_response (
  response_id int(11) unsigned NOT NULL auto_increment,
  msg_id int(11) unsigned NOT NULL default '0',
  ticket_id int(11) unsigned NOT NULL default '0',
  staff_id int(11) unsigned NOT NULL default '0',
  staff_name varchar(32) NOT NULL default '',
  response text NOT NULL,
  ip_address varchar(16) NOT NULL default '',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (response_id),
  KEY ticket_id (ticket_id),
  KEY msg_id (msg_id),
  KEY staff_id (staff_id)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS %TABLE_PREFIX%timezone;
CREATE TABLE %TABLE_PREFIX%timezone (
  id int(11) unsigned NOT NULL auto_increment,
  `offset` float(3,1) NOT NULL default '0.0',
  timezone varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) ENGINE=MyISAM;


INSERT INTO %TABLE_PREFIX%timezone VALUES (1,'-12','Eniwetok, Kwajalein');
INSERT INTO %TABLE_PREFIX%timezone VALUES (2,'-11','Midway Island, Samoa');
INSERT INTO %TABLE_PREFIX%timezone VALUES (3,'-10','Hawaii');
INSERT INTO %TABLE_PREFIX%timezone VALUES (4,'-9','Alaska');
INSERT INTO %TABLE_PREFIX%timezone VALUES (5,'-8','Pacific Time (US & Canada)');
INSERT INTO %TABLE_PREFIX%timezone VALUES (6,'-7','Mountain Time (US & Canada)');
INSERT INTO %TABLE_PREFIX%timezone VALUES (7,'-6','Central Time (US & Canada), Mexico City');
INSERT INTO %TABLE_PREFIX%timezone VALUES (8,'-5','Eastern Time (US & Canada), Bogota, Lima');
INSERT INTO %TABLE_PREFIX%timezone VALUES (9,'-4','Atlantic Time (Canada), Caracas, La Paz');
INSERT INTO %TABLE_PREFIX%timezone VALUES (10,'-3.5','Newfoundland');
INSERT INTO %TABLE_PREFIX%timezone VALUES (11,'-3','Brazil, Buenos Aires, Georgetown');
INSERT INTO %TABLE_PREFIX%timezone VALUES (12,'-2','Mid-Atlantic');
INSERT INTO %TABLE_PREFIX%timezone VALUES (13,'-1','Azores, Cape Verde Islands');
INSERT INTO %TABLE_PREFIX%timezone VALUES (14,'0','Western Europe Time, London, Lisbon, Casablanca');
INSERT INTO %TABLE_PREFIX%timezone VALUES (15,'1','Brussels, Copenhagen, Madrid, Paris');
INSERT INTO %TABLE_PREFIX%timezone VALUES (16,'2','Kaliningrad, South Africa');
INSERT INTO %TABLE_PREFIX%timezone VALUES (17,'3','Baghdad, Riyadh, Moscow, St. Petersburg');
INSERT INTO %TABLE_PREFIX%timezone VALUES (18,'3.5','Tehran');
INSERT INTO %TABLE_PREFIX%timezone VALUES (19,'4','Abu Dhabi, Muscat, Baku, Tbilisi');
INSERT INTO %TABLE_PREFIX%timezone VALUES (20,'4.5','Kabul');
INSERT INTO %TABLE_PREFIX%timezone VALUES (21,'5','Ekaterinburg, Islamabad, Karachi, Tashkent');
INSERT INTO %TABLE_PREFIX%timezone VALUES (22,'5.5','Bombay, Calcutta, Madras, New Delhi');
INSERT INTO %TABLE_PREFIX%timezone VALUES (23,'6','Almaty, Dhaka, Colombo');
INSERT INTO %TABLE_PREFIX%timezone VALUES (24,'7','Bangkok, Hanoi, Jakarta');
INSERT INTO %TABLE_PREFIX%timezone VALUES (25,'8','Beijing, Perth, Singapore, Hong Kong');
INSERT INTO %TABLE_PREFIX%timezone VALUES (26,'9','Tokyo, Seoul, Osaka, Sapporo, Yakutsk');
INSERT INTO %TABLE_PREFIX%timezone VALUES (27,'9.5','Adelaide, Darwin');
INSERT INTO %TABLE_PREFIX%timezone VALUES (28,'10','Eastern Australia, Guam, Vladivostok');
INSERT INTO %TABLE_PREFIX%timezone VALUES (29,'11','Magadan, Solomon Islands, New Caledonia');
INSERT INTO %TABLE_PREFIX%timezone VALUES (30,'12','Auckland, Wellington, Fiji, Kamchatka');
