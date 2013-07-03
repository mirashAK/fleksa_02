-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Хост: 127.0.0.1:3306
-- Час створення: Трв 14 2013 р., 19:44
-- Версія сервера: 5.1.68-community-log
-- Версія PHP: 5.3.23

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База даних: `fleksa`
--

DELIMITER $$
--
-- Процедури
--
CREATE DEFINER=`root`@`%` PROCEDURE `create_table`(IN in_table_name VARCHAR(40), IN in_columns TEXT,
	IN in_sel TINYINT UNSIGNED, IN in_upd TINYINT UNSIGNED, IN in_ins TINYINT UNSIGNED, IN in_del TINYINT UNSIGNED)
    READS SQL DATA
BEGIN
	DECLARE var_query VARCHAR(255);
	DECLARE var_field_prefix VARCHAR(40) DEFAULT '';
	DECLARE var_creator TEXT DEFAULT '';
	DECLARE var_insertor TEXT DEFAULT '';
	DECLARE var_col_type VARCHAR(40) DEFAULT '';
	DECLARE var_tmp_columns TEXT DEFAULT '';

	DECLARE var_position TINYINT UNSIGNED DEFAULT 1;
	DECLARE var_start TINYINT UNSIGNED DEFAULT 1;

	SET in_table_name = LOWER(in_table_name);
	SET in_columns = CONCAT(TRIM(in_columns),',');

	/* Get fields prefixes */
	SET var_field_prefix = MID(in_table_name, 1, 1);
	WHILE LOCATE('_', in_table_name, var_position) > 0 DO
		SET var_position = LOCATE('_', in_table_name, var_position) + 1;
		SET var_field_prefix = CONCAT(var_field_prefix, MID(in_table_name, var_position, 1));
	END WHILE;

	/*INSERT INTO `syst_defs_columns` (`table_name`,`field_name`,`field_type`,`field_read_level`,`field_write_level`) VALUES (1,2,3),(4,5,6),(7,8,9);*/
	SET var_insertor = CONCAT("('public_",in_table_name,"','",var_field_prefix,"_id', 'id',",in_sel,", 90)");
	
	/* Loop through fields definions and construct CREATE instruction */
	WHILE (LOCATE(',', in_columns, var_start) > 0) DO
		SET var_position = LOCATE(',', in_columns, var_start);
		SET var_tmp_columns = TRIM(MID(in_columns, var_start, var_position - var_start));

		IF (var_tmp_columns != '') THEN IF (LOCATE('=', var_tmp_columns)>0) THEN

			SET var_col_type = SUBSTRING_INDEX(var_tmp_columns, '=', -1);

			/* Construct fields defs for syst_defs_columns */
			SET var_insertor = CONCAT(var_insertor, ",('public_",in_table_name,"','",var_field_prefix,'_',SUBSTRING_INDEX(var_tmp_columns, '=', 1),"','",var_col_type,"',",in_sel,',',in_upd,')');

			SET @query = CONCAT("SELECT `sct_type` INTO @sct_type FROM `syst_column_types` WHERE `sct_alias` LIKE '", var_col_type,"' LIMIT 1");
			PREPARE var_query FROM @query; EXECUTE var_query; DEALLOCATE PREPARE var_query;
			
			IF (@sct_type IS NOT NULL) THEN SET var_col_type = @sct_type; END IF;

			/* Construct fields defs for targeted table */
			SET var_creator = CONCAT(var_creator,' `',var_field_prefix,'_',SUBSTRING_INDEX(var_tmp_columns, '=', 1),'` ',var_col_type,' DEFAULT NULL, ');	
			
		END IF; END IF;
		SET var_start = var_position + 1;
	END WHILE;

	SET var_creator = CONCAT('CREATE TABLE IF NOT EXISTS `public_',in_table_name,'` ( `', var_field_prefix, '_id` int(11) NOT NULL AUTO_INCREMENT, ', var_creator);
	SET var_creator = CONCAT(var_creator, ' `read_level` int(11) NOT NULL DEFAULT 0, `write_level` int(11) NOT NULL DEFAULT 0, `owner` int(11) NOT NULL DEFAULT 0,');
	SET var_creator = CONCAT(var_creator, ' PRIMARY KEY (`', var_field_prefix, '_id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');
	SET @query = var_creator;
	PREPARE var_query FROM @query; EXECUTE var_query; DEALLOCATE PREPARE var_query;

	SET var_insertor = CONCAT('INSERT INTO `syst_defs_columns` (`table_name`,`field_name`,`field_type`,`field_read_level`,`field_write_level`) VALUES ',var_insertor);
	SET @query = var_insertor;
	PREPARE var_query FROM @query; EXECUTE var_query; DEALLOCATE PREPARE var_query;

	SET var_insertor = 'INSERT INTO `syst_defs_tables` (`table_name`,`table_read_level`,`table_write_level`,`table_insert_level`,`table_delete_level`) VALUES';
	SET var_insertor = CONCAT(var_insertor," ('public_",in_table_name,"',",in_sel,',',in_upd,',',in_ins,',',in_del,')');
	SET @query = var_insertor;
	PREPARE var_query FROM @query; EXECUTE var_query; DEALLOCATE PREPARE var_query;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_columns_defs`(IN in_table_name varchar(40), IN in_read_level INT, IN in_write_level INT, OUT out_columns varchar(255), OUT out_defs varchar(512))
BEGIN
	DECLARE var_is_end BOOLEAN DEFAULT false;
	DECLARE var_column varchar(100) DEFAULT "";
	DECLARE var_def varchar(100) DEFAULT "";
	DECLARE var_caption varchar(100) DEFAULT "";
	DECLARE result_cursor CURSOR FOR SELECT `field_name`,`field_type`,`field_caption` FROM `syst_defs_columns` WHERE `table_name` = in_table_name AND `field_read_level` <= in_read_level AND `field_write_level` <= in_write_level;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET var_is_end = true;

	OPEN result_cursor;
-- '{"name":{"type":"type","caption":"caption","value":"value"}}'
	FETCH result_cursor INTO var_column, var_def, var_caption;
	IF var_is_end != true THEN
		SET out_columns = CONCAT ("`", var_column);
		SET out_defs = CONCAT ('{"', var_column, '":{"type":"', var_def, '","caption":"', var_caption, '"}');
		WHILE var_is_end != true DO
			FETCH result_cursor INTO var_column, var_def, var_caption;
			IF var_is_end != true THEN
				SET out_columns = CONCAT (out_columns, "`,`", var_column);
				SET out_defs = CONCAT (out_defs, ', "', var_column, '":{"type":"', var_def, '","caption":"', var_caption, '"}');
			END IF;
		END WHILE;
		SET out_columns = CONCAT (out_columns, "`");
		SET out_defs = CONCAT (out_defs, "}");
	END IF;

	CLOSE result_cursor;
END$$

CREATE DEFINER=`root`@`%` PROCEDURE `get_signature`(IN in_user_token VARCHAR(40), IN in_ip VARCHAR(15), IN in_table_name varchar(40))
    READS SQL DATA
BEGIN
	DECLARE var_query VARCHAR(255);
	DECLARE var_active_user_id INT;
	/* Variables for permitted columns */
	DECLARE var_selected_columns VARCHAR(255);
	DECLARE var_selected_defs VARCHAR(255);
	DECLARE var_selected_captions VARCHAR(255);
	/* Security checking input values */
	SET in_table_name = clear_input_data(in_table_name);
	SET in_user_token = clear_input_data(in_user_token);
	SET in_ip = clear_input_data(in_ip);

	/* Check if user token is valid */
	SELECT check_token (in_user_token, in_ip) INTO var_active_user_id;
	IF (var_active_user_id < 0) THEN
        SELECT "ERROR: Token not found";
    ELSE /* If user is valid */
	/* Check user group and get read_access_level*/
		IF (var_active_user_id > 0) THEN
		SET @query = CONCAT("SELECT `group_read_level` INTO @read_level FROM `syst_groups` WHERE `group_id` = (SELECT `user_f_group_id`  FROM `syst_users` WHERE `user_id` = ", var_active_user_id, " LIMIT 1) LIMIT 1");
		ELSE SET @query = CONCAT("SELECT `group_read_level` INTO @read_level FROM `syst_groups` WHERE `group_name` = 'guests' LIMIT 1");
		END IF;
		PREPARE var_query FROM @query;
		EXECUTE var_query;
		DEALLOCATE PREPARE var_query;

		CALL get_columns_defs(in_table_name, @read_level, 100, @var_selected_columns, @var_selected_defs);

		/* Give out columns definions */
		SELECT @var_selected_defs AS columns_defs;
	END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_table`(IN in_user_token VARCHAR(40), IN in_ip VARCHAR(15), IN in_table_name varchar(40), IN in_where varchar(100), IN in_order varchar(100), IN in_limit varchar(30))
    READS SQL DATA
BEGIN
	DECLARE var_query VARCHAR(255);
	DECLARE var_conditions VARCHAR(255) DEFAULT "";
	DECLARE var_active_user_id INT;
	/* Variables for permitted columns */
	DECLARE var_selected_columns VARCHAR(255);
	DECLARE var_selected_defs VARCHAR(255);
	DECLARE var_selected_captions VARCHAR(255);
	/* Security checking input values */
	SET in_table_name = clear_input_data(in_table_name);
	SET in_user_token = clear_input_data(in_user_token);
	SET in_ip = clear_input_data(in_ip);
	SET in_where = REPLACE(REPLACE(REPLACE(LOWER(in_where),'update',''),'insert',''),';','');
	SET in_order = clear_input_data(in_order);
	SET in_limit = clear_input_data(in_limit);

	/* Check if user token is valid */
	SELECT check_token (in_user_token, in_ip) INTO var_active_user_id;
	IF (var_active_user_id < 0) THEN
        SELECT "ERROR: Token not found";
    ELSE /* If user is valid */
		/* Check user group and get read_access_level*/
		IF (var_active_user_id > 0) THEN
		SET @query = CONCAT("SELECT `group_read_level` INTO @read_level FROM `syst_groups` WHERE `group_id` = (SELECT `user_f_group_id`  FROM `syst_users` WHERE `user_id` = ", var_active_user_id, " LIMIT 1) LIMIT 1");
		ELSE SET @query = CONCAT("SELECT `group_read_level` INTO @read_level FROM `syst_groups` WHERE `group_name` = 'guests' LIMIT 1");
		END IF;
		PREPARE var_query FROM @query;
		EXECUTE var_query;
		DEALLOCATE PREPARE var_query;

		CALL get_columns_defs(in_table_name, @read_level, 100, @var_selected_columns, @var_selected_defs);

		/* Give out columns definions */
		SELECT @var_selected_defs AS columns_defs;

		/* Form conditions */
		IF CHAR_LENGTH(in_where)>0 THEN
			SET var_conditions = CONCAT(var_conditions, " WHERE (", in_where, ")");
			SET var_conditions = CONCAT(var_conditions, " AND (`owner` = ", var_active_user_id, " OR `read_level` <= ", @read_level,")");
		ELSE
			SET var_conditions = CONCAT(var_conditions, " WHERE (`owner` = ", var_active_user_id, " OR `read_level` <= ", @read_level,")");
		END IF;

		/* Try to count rows */
		SET @query = CONCAT("SELECT COUNT(*) INTO @all_rows_count FROM `", in_table_name, "`", var_conditions);
		PREPARE var_query FROM @query;
		EXECUTE var_query;
		DEALLOCATE PREPARE var_query;

		/* Form other conditions */
		IF CHAR_LENGTH(in_order)>0 THEN SET var_conditions = CONCAT(var_conditions, " ORDER BY ", in_order); END IF;
		IF CHAR_LENGTH(in_limit)>0 THEN SET var_conditions = CONCAT(var_conditions, " LIMIT ", in_limit); END IF;

		
		/* Main result query */
		SET @query = CONCAT("SELECT ", @var_selected_columns, " FROM `", in_table_name, "`", var_conditions);
		-- SELECT @query;
		PREPARE var_query FROM @query;
		EXECUTE var_query;
		DEALLOCATE PREPARE var_query; 

		/* Give out total rows count */
		SELECT @all_rows_count AS total_count;

    END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_users`(IN in_user_token varchar(40), IN in_ip varchar(15))
    READS SQL DATA
BEGIN
    DECLARE var_token_is_active TINYINT;

    SELECT check_token (in_user_token, in_ip) INTO var_token_is_active;

   
    IF (var_token_is_active = 0) THEN
        SELECT 'ERROR: Token not found';
    ELSE SELECT in_ip;
    END IF;

END$$

--
-- Функції
--
CREATE DEFINER=`root`@`localhost` FUNCTION `add_user`(in_login varchar(150), in_email varchar(150), in_pass varchar(250), in_salt varchar(40)) RETURNS varchar(40) CHARSET utf8
    READS SQL DATA
BEGIN
	/* Security checking input values */
	SET in_login = TRIM(REPLACE(REPLACE(in_login,'=',''),';',''));
	SET in_email = TRIM(REPLACE(REPLACE(in_email,'=',''),';',''));
	SET in_pass = TRIM(REPLACE(REPLACE(in_pass,'=',''),';',''));
	SET in_salt = TRIM(REPLACE(REPLACE(in_salt,'=',''),';',''));
	
	IF (in_login = '') && (in_email = '') THEN
        RETURN "ERROR: Auth key not found";
    END IF;
	IF (in_pass = '') THEN
        RETURN "ERROR: Password not setted";
    END IF;

	SET in_pass = gen_passwd (in_pass, in_salt);

	INSERT INTO `syst_users` (`user_login`,`user_email`,`user_pass`) VALUES (in_login, in_email, in_pass);

	RETURN LAST_INSERT_ID();

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `check_token`(`in_user_token` VARCHAR(40), `in_ip` VARCHAR(15)) RETURNS int(11)
    READS SQL DATA
BEGIN
    DECLARE var_user_id INT;
	DECLARE var_sess_id INT;
    DECLARE var_user_is_active TINYINT;
    DECLARE var_user_last_ip VARCHAR(15);
    DECLARE var_user_auth_time VARCHAR(25);

    DECLARE var_token_life_time VARCHAR(20);

    SET in_user_token = clear_input_data (in_user_token);
    SET in_ip = clear_input_data (in_ip);

    IF (in_user_token = '') THEN
        RETURN 0;
    END IF;

	/* Try to get user session data */
    SELECT `sess_id`, `sess_user_last_ip`, `sess_user_last_activity`, `sess_f_user_id`
    INTO var_sess_id, var_user_last_ip, var_user_auth_time, var_user_id
    FROM `syst_sessions` 
    WHERE ( `sess_user_token` = in_user_token )
    LIMIT 1;

    CASE
		/* If token not found */
		WHEN var_sess_id IS NULL THEN RETURN -1; 

		/* If found guest session */
       WHEN var_user_id = 0 THEN

			/* Update last user activity */
			UPDATE `syst_sessions` SET `sess_user_last_activity` = NOW(), `sess_user_last_ip` = in_ip
			WHERE `sess_id` = var_sess_id LIMIT 1;
			RETURN 0; /* Guest session */

		/* If found user session */
       WHEN var_user_id > 0 THEN

			/* Check if user is active */
			SELECT `user_is_active` INTO var_user_is_active FROM `syst_users`
			WHERE `user_id` = var_user_id LIMIT 1;

			IF var_user_is_active IS NOT NULL THEN
			
				/* Get token life time */
				SELECT `sett_user_token_time` INTO var_token_life_time FROM `syst_settings`
				WHERE `sett_id` = 1 LIMIT 1;
				/* Calculate time */
				SET var_token_life_time = UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(ADDTIME(var_user_auth_time, var_token_life_time));
				SET var_token_life_time = CAST(var_token_life_time as SIGNED);

				IF (var_user_is_active = 0) || (var_user_last_ip != in_ip) || (var_token_life_time > 0) THEN

					/* Destroy user token */
					UPDATE `syst_sessions` SET `sess_user_token` = NULL
					WHERE `sess_id` = var_sess_id LIMIT 1;
					RETURN -2; /* Expired, user not active or different ip */

			    ELSE

					/* Update last user activity */
					UPDATE `syst_sessions` SET `sess_user_last_activity` = NOW()
					WHERE `sess_id` = var_sess_id LIMIT 1;
					RETURN var_user_id;

			    END IF;
			/* If user not found in sysy_users table */
			ELSE RETURN -3;
			END IF;

	END CASE;

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `clear_input_data`( in_data varchar(255) ) RETURNS varchar(255) CHARSET utf8
    NO SQL
BEGIN
	SET in_data = TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(in_data,'%',''),'"',''),"'",''),'<',''),'>',''),')',''),'(',''),'=',''),';',''));
	RETURN in_data;  
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `do_auth`(auth_key varchar(150), pass varchar(255), ip varchar(15)) RETURNS varchar(40) CHARSET utf8
    READS SQL DATA
BEGIN
	DECLARE var_user_id INT;
	DECLARE var_sess_id INT;
	DECLARE var_user_token CHAR( 40 );
	DECLARE var_user_is_active TINYINT;
	SET auth_key = clear_input_data (auth_key);
	SET pass = clear_input_data (pass);
	SET ip = clear_input_data (ip);

    IF (auth_key = '') || (ip = '') THEN
        RETURN 'ERROR: Some value not set';
    END IF;

	IF (auth_key != 'guest') THEN
		/* If user */
		SET pass = gen_passwd (pass);
		/* Try to get user */
		SELECT `user_id`, `user_is_active` INTO var_user_id, var_user_is_active FROM `syst_users` 
       WHERE `user_email` = auth_key AND `user_pass` = pass 
       LIMIT 1;

		IF (var_user_id IS NULL) THEN
            RETURN 'ERROR: Combination not found';
		END IF;
       IF (var_user_is_active = 0) THEN
            RETURN 'ERROR: User not activated';
		END IF;

			/* Check if session record allready exists*/
			SELECT `sess_id` INTO var_sess_id FROM `syst_sessions`
			WHERE `sess_f_user_id` = var_user_id LIMIT 1;
			
			IF var_sess_id IS NULL THEN
				/* If record not found */
				SET var_user_token = MD5 ( CONCAT( auth_key, ip, NOW() ) );
				INSERT INTO `syst_sessions` (`sess_f_user_id`, `sess_user_token`, `sess_user_last_ip`, `sess_user_last_activity`)
				VALUES (var_user_id, var_user_token, ip, NOW());
				RETURN var_user_token;
			ELSE
				/* If record found */
			    SET var_user_token = MD5 ( CONCAT( auth_key, ip, NOW() ) );
			    UPDATE `syst_sessions` SET `sess_user_token` = var_user_token, `sess_user_last_ip` = ip, `sess_user_last_activity` = NOW()
			    WHERE `sess_id` = var_sess_id LIMIT 1;
			    RETURN var_user_token;

		END IF;

	ELSE 
		/* If guest */
		SET var_user_token = MD5 ( CONCAT( auth_key, ip, NOW() ) );
		INSERT INTO `syst_sessions` (`sess_f_user_id`, `sess_user_token`, `sess_user_last_ip`, `sess_user_last_activity`)
		VALUES (0, var_user_token, ip, NOW());
		RETURN var_user_token;
	END IF;

END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `gen_passwd`(in_pass varchar(250), in_salt varchar(40)) RETURNS varchar(80) CHARSET utf8
    READS SQL DATA
BEGIN

	DECLARE var_passw_salt CHAR( 40 );
	/* Security checking input values */
	SET in_pass = TRIM(REPLACE(REPLACE(in_pass,'=',''),';',''));
	SET in_salt = TRIM(REPLACE(REPLACE(in_salt,'=',''),';',''));

	IF (in_pass = '') THEN
        RETURN "ERROR: Password not setted";
    END IF;

	IF (in_salt = '') THEN
		/* Get salt value from sett table */
		SELECT `sett_passw_salt` INTO var_passw_salt FROM `syst_settings` LIMIT 1;
	ELSE
		SET var_passw_salt = in_salt;
	END IF;

	RETURN ENCRYPT(in_pass, var_passw_salt);

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблиці `hrefs_articles_to_categories`
--

CREATE TABLE IF NOT EXISTS `hrefs_articles_to_categories` (
  `href_cat_id` int(11) NOT NULL,
  `href_article_id` int(11) NOT NULL,
  `read_level` int(11) NOT NULL DEFAULT '0',
  `write_level` int(11) NOT NULL DEFAULT '0',
  `owner` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп даних таблиці `hrefs_articles_to_categories`
--

INSERT INTO `hrefs_articles_to_categories` (`href_cat_id`, `href_article_id`, `read_level`, `write_level`, `owner`) VALUES
(1, 1, 0, 50, 1),
(1, 2, 0, 50, 1),
(2, 1, 0, 50, 1),
(2, 2, 0, 50, 1);

-- --------------------------------------------------------

--
-- Структура таблиці `public_articles`
--

CREATE TABLE IF NOT EXISTS `public_articles` (
  `article_id` int(11) NOT NULL AUTO_INCREMENT,
  `article_name` varchar(255) NOT NULL,
  `article_alias` varchar(255) NOT NULL,
  `article_text` text,
  `read_level` int(11) NOT NULL DEFAULT '0',
  `write_level` int(11) NOT NULL DEFAULT '0',
  `owner` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`article_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп даних таблиці `public_articles`
--

INSERT INTO `public_articles` (`article_id`, `article_name`, `article_alias`, `article_text`, `read_level`, `write_level`, `owner`) VALUES
(1, 'Тестовая статья 1', 'article-1', '<p>Apple победила Samsung в США, но ей не удалось этого сделать в Великобритании. Еще летом британский суд отклонил обвинение Apple в том, что Samsung скопировала дизайн iPad для своих планшетов Galaxy Tab. Более того, судья Колин Бирсс (Colin Birss) сделал это весьма забавным образом, сказав, что «планшеты Samsung не настолько круты, как iPad». А раз они не так хороши, то и о каком-то заимствовании речи быть не может.</p>\r\n<hr class="separator" />\r\n<p>Apple не сдалась, подала на апелляцию на это решение и получила еще один похожий ответ, но с последствиями для себя, сообщает The Verge.</p>', 0, 50, 1),
(2, 'Тестовая статья 2', 'article-2', '<p>Только вчера появилась информация об уровне цен на планшеты Microsoft Surface RT с новой ОС Windows 8. А уже сегодня стало известно, что по предварительным заказам уже были раскуплены все имеющиеся в наличии планшеты с 32 ГБ флэш-памяти. Напомним, эта наиболее доступная модель без обложки-клавиатуры в комплекте обладает ценой $499. В продаже пока еще доступны более дорогие устройства, за которые Microsoft просит $599 и $699.</p>\r\n<hr class="separator" />\r\n<p>Совсем немного времени прошло с момента официального релиза новой операционной системы Windows 8, а компания Microsoft уже столкнулась с судебным разбирательством, вызванным этой ОС.</p>\r\n\r\n<p>Так, компания SurfCast обратилась с исковым заявлением в Окружной суд США в штате Мэн (U.S. District Court in Maine), в котором обвиняет Microsoft в нарушении одного из четырех принадлежащих ей патентов - №6,724,403. Этот патент описывает технологию плиточного интерфейса для мобильных устройств. Патент датирован 20 апреля 2004 года. Плиточный интерфейс является одним из отличительных признаков настольной операционной системы Windows 8. Он также используется и в мобильных версиях ОС Windows Phone 7 и Windows Phone 8. Таким образом, SurfCast обвиняет Microsoft в создании, использовании, продаже и предложении продавать программные продукты и аппаратные решения, в которых незаконно используется указанный патент. Кроме того, Microsoft, якобы, способствует дальнейшему нарушению патента, побуждая сторонних разработчиков программного обеспечения создавать приложения с плитками интерфейса для магазина Windows Store. В связи с этим SurfCast требует, чтобы суд установил факт прямого и косвенного нарушения патента со стороны Microsoft. Также в иске указано требование обязать Microsoft подсчитать и выплатить в пользу SurfCast все убытки, связанные с нарушением патента.</p>\r\n\r\n<p>В свою очередь представитель Microsoft назвал обвинения в незаконном использовании указанного патента необоснованными. Компания намерена доказать в суде, что она создала уникальный пользовательский опыт взаимодействия с ОС.</p>', 0, 50, 1);

-- --------------------------------------------------------

--
-- Структура таблиці `public_articles_new_test`
--

CREATE TABLE IF NOT EXISTS `public_articles_new_test` (
  `ant_id` int(11) NOT NULL AUTO_INCREMENT,
  `ant_name` varchar(100) DEFAULT NULL,
  `ant_alias` varchar(100) DEFAULT NULL,
  `ant_email` varchar(50) DEFAULT NULL,
  `ant_data` text,
  `read_level` int(11) NOT NULL DEFAULT '0',
  `write_level` int(11) NOT NULL DEFAULT '0',
  `owner` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ant_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблиці `public_categories`
--

CREATE TABLE IF NOT EXISTS `public_categories` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(255) NOT NULL,
  `cat_alias` varchar(255) NOT NULL,
  `cat_parent_id` int(11) NOT NULL DEFAULT '0',
  `read_level` int(11) NOT NULL DEFAULT '0',
  `write_level` int(11) NOT NULL DEFAULT '0',
  `owner` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп даних таблиці `public_categories`
--

INSERT INTO `public_categories` (`cat_id`, `cat_name`, `cat_alias`, `cat_parent_id`, `read_level`, `write_level`, `owner`) VALUES
(1, 'Публикации', 'articles', 0, 0, 80, 1),
(2, 'Новости', 'news', 0, 0, 80, 1);

-- --------------------------------------------------------

--
-- Структура таблиці `public_main_menu`
--

CREATE TABLE IF NOT EXISTS `public_main_menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_item_name` varchar(100) NOT NULL,
  `menu_item_link` varchar(255) NOT NULL,
  `menu_item_parent` int(11) NOT NULL DEFAULT '0',
  `menu_item_order` int(11) NOT NULL DEFAULT '0',
  `read_level` int(11) NOT NULL,
  `write_level` int(11) NOT NULL,
  `owner` int(11) NOT NULL,
  PRIMARY KEY (`menu_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Дамп даних таблиці `public_main_menu`
--

INSERT INTO `public_main_menu` (`menu_id`, `menu_item_name`, `menu_item_link`, `menu_item_parent`, `menu_item_order`, `read_level`, `write_level`, `owner`) VALUES
(1, 'main_menu', '', 0, 0, 0, 80, 1),
(2, 'bottom_menu', '', 0, 0, 0, 80, 1),
(3, 'Про компанию', 'home', 1, 1, 0, 80, 1),
(4, 'Услуги', 'services', 1, 2, 0, 80, 1),
(5, 'Публикации', 'articles', 1, 3, 0, 80, 1),
(6, 'Заявка на экспертную оценку', 'forms/request', 1, 4, 0, 80, 1),
(7, 'Контакты', 'contacts', 1, 5, 0, 80, 1);

-- --------------------------------------------------------

--
-- Структура таблиці `public_sessions_data`
--

CREATE TABLE IF NOT EXISTS `public_sessions_data` (
  `sess_data_id` int(11) NOT NULL AUTO_INCREMENT,
  `sess_data_f_user_id` int(11) NOT NULL,
  `sess_data_f_sess_token` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `sess_data_user_data` text,
  `owner` int(11) NOT NULL,
  `read_level` int(11) NOT NULL,
  `write_level` int(11) NOT NULL,
  PRIMARY KEY (`sess_data_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Saved session data' AUTO_INCREMENT=2 ;

--
-- Дамп даних таблиці `public_sessions_data`
--

INSERT INTO `public_sessions_data` (`sess_data_id`, `sess_data_f_user_id`, `sess_data_f_sess_token`, `sess_data_user_data`, `owner`, `read_level`, `write_level`) VALUES
(1, 0, 'e598d041cbcbb369517544fd64e53331', '{"name": "Hello World"}', 0, 0, 0);

-- --------------------------------------------------------

--
-- Структура таблиці `public_static_pages`
--

CREATE TABLE IF NOT EXISTS `public_static_pages` (
  `static_page_id` int(11) NOT NULL AUTO_INCREMENT,
  `static_page_alias` varchar(255) NOT NULL,
  `static_page_text` text NOT NULL,
  `owner` int(11) NOT NULL,
  `read_level` int(11) NOT NULL,
  `write_level` int(11) NOT NULL,
  PRIMARY KEY (`static_page_id`),
  UNIQUE KEY `static_page_alias` (`static_page_alias`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Дамп даних таблиці `public_static_pages`
--

INSERT INTO `public_static_pages` (`static_page_id`, `static_page_alias`, `static_page_text`, `owner`, `read_level`, `write_level`) VALUES
(1, 'home', '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus gravida, ipsum a pretium suscipit, ligula eros semper ante, in pretium mi nibh nec lorem. Maecenas iaculis libero ut nibh auctor gravida. Suspendisse sed sapien neque. Praesent aliquet, nibh at aliquam faucibus, felis sapien facilisis mi, quis blandit eros risus vel metus. Suspendisse eget facilisis purus. Sed eu urna laoreet felis blandit porta. Morbi ornare bibendum bibendum.</p>\r\n\r\n<p>Nam tincidunt cursus arcu, sed vestibulum tortor egestas eu. Proin lobortis, metus quis malesuada lobortis, est elit semper felis, sed convallis nunc augue ac justo. Curabitur tincidunt nisi id tellus placerat et laoreet augue posuere. Nulla mattis, neque et adipiscing sagittis, metus diam hendrerit purus, id semper leo lectus vel velit. Mauris nulla eros, tempor vel molestie nec, congue vel risus. In vitae ullamcorper metus. Aliquam risus justo, facilisis non molestie et, molestie vel orci. Donec vel arcu diam, et rutrum dolor. Nullam pharetra facilisis neque varius mollis. Quisque scelerisque luctus sodales. Sed vitae eleifend libero. Nullam euismod libero sed turpis egestas egestas. Vestibulum ullamcorper ante pellentesque quam egestas tempus. Donec lacus dolor, lacinia fermentum malesuada eget, sollicitudin a augue.</p>\r\n\r\n<p>Maecenas tristique cursus urna, ac adipiscing libero suscipit vel. Nunc elementum mattis leo in euismod. Fusce commodo auctor consequat. In volutpat est pharetra nibh iaculis convallis. Nam scelerisque dui id turpis aliquet id pellentesque tortor ullamcorper. Nunc nec turpis id magna mattis commodo vel nec eros. Aenean eget metus erat, in iaculis nunc. Integer in diam risus. Aliquam mollis, nisi vitae lobortis aliquam, eros erat ornare justo, id ultricies erat quam eget felis. Maecenas orci libero, porttitor nec tempus hendrerit, ullamcorper eget quam. Integer lorem nunc, ultrices eu tincidunt at, dapibus et lectus. Cras magna elit, dignissim eget dignissim nec, interdum et sem.</p>\r\n\r\n<p>Morbi ornare ornare nisi, eget semper velit tempus eget. Fusce vitae ipsum nisl, eget viverra enim. Sed aliquam placerat mauris. Quisque et magna vel tortor rhoncus sagittis ac eu neque. Maecenas interdum pharetra quam at sollicitudin. Nunc metus libero, imperdiet at ullamcorper a, faucibus sed quam. Morbi non molestie metus. Cras viverra justo non nisi accumsan eget mattis ligula tempor. Nam varius, quam a iaculis sodales, dui mauris aliquet nibh, sit amet faucibus justo nibh et dolor. Vestibulum nec nisl et mi pulvinar semper sit amet nec eros.</p>\r\n\r\n<p>Nullam eu purus massa. Ut sed cursus nibh. Praesent vulputate odio aliquet odio auctor volutpat. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Aenean dictum luctus neque a molestie. Suspendisse convallis feugiat turpis, sit amet lacinia quam gravida in. Mauris sollicitudin lacus a felis accumsan molestie. Aliquam vehicula eros quis leo placerat mattis. Donec sodales eros quis mauris luctus porttitor. Quisque orci eros, consectetur ac sodales euismod, posuere ut massa. Suspendisse vel felis ante, at dignissim tellus. Nam id pharetra purus. Nullam viverra, odio nec scelerisque aliquam, massa metus porttitor diam, id ullamcorper turpis dolor vitae leo. Vestibulum mattis congue lorem, in placerat mi pharetra at. Nullam in diam massa, vitae ultricies leo. Ut elementum, nisl sed blandit dignissim, justo lacus rutrum ligula, ut luctus est neque ut nulla.</p> ', 1, 20, 50),
(2, 'contacts', '<p>agle не способен подключаться к сотовым сетям или сетям Wi-Fi, однако в устройстве есть модуль Bluetooth. Это позволяет пользователю отправлять на читалку книги с компьютера, со смартфона или с планшета.\r\n\r\nЕще одна особенности немецкой читалки - элементы питания: Beagle работает от двух батареек типоразмера AAA. Они помещаются в "утолщение" в нижней части устройства. Менять батарейки пользователю придется реже, чем раз в год, утверждает производитель.\r\n\r\nTxtr назвала цену устройств (9,9 евро), однако не уточнила, когда именно Beagle поступит в продажу. Известно, что читалку можно будет купить в Европе. Компания также рассматривает возможность поставлять устройство в Азию и в США.\r\n\r\nЭкраны на электронных чернилах, которые используются в большинстве читалок, расходуют электроэнергию только в момент обновления изображения - в частности, при перелистывании страницы. Благодаря этому устройства с такими экранами могут работать от батареи гораздо дольше, чем устройства с ЖК-экранами.</p>', 1, 20, 50),
(3, 'services', '<p>События, о которых пойдёт речь, точнее - явная сторона этих событий, как кажется, хорошо известны, детально изучены и приобрели колоссальное значение во всех аспектах жизни человечества.\r\n\r\nПроизойдя почти две тысячи лет назад, они приобрели и историческое, и научное, и философское, и культовое значение такой пронзительной мощности, что сегодня уже, в общем-то, и не важно, какова была их истинная подоплёка. Так, механизм зарождения космического тела, предположительно обрушившегося на Землю 65 миллионов лет назад и вызвавшего гигантские биологические сдвиги на нашей планете, не имеет для нас существенного значения в сравнении с этими последствиями1 .\r\n\r\nОднако, как ни курьёзно это звучит, но о событиях относительно столь недавних (подумаешь - 2000 лет! - всего лишь 25 человек, проживших один за другим по 80 лет) свидетельств очевидцев почти так же мало как о событиях доисторического периода. За пределами христианского предания о них почти не упоминается, а само это предание проходило столько редакционно-цензурных переработок, доработок, чисток, переводов, что даже явная сторона событий допускает известную вольность трактовок.</p>', 1, 0, 0);

-- --------------------------------------------------------

--
-- Структура таблиці `public_users_info`
--

CREATE TABLE IF NOT EXISTS `public_users_info` (
  `user_info_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_info_f_user_id` int(11) NOT NULL,
  PRIMARY KEY (`user_info_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблиці `syst_column_types`
--

CREATE TABLE IF NOT EXISTS `syst_column_types` (
  `sct_id` int(11) NOT NULL AUTO_INCREMENT,
  `sct_alias` varchar(255) DEFAULT NULL,
  `sct_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`sct_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Column type definitions for create_table function' AUTO_INCREMENT=5 ;

--
-- Дамп даних таблиці `syst_column_types`
--

INSERT INTO `syst_column_types` (`sct_id`, `sct_alias`, `sct_type`) VALUES
(1, 'json', 'TEXT'),
(2, 'id', 'INT(11)'),
(3, 'pass', 'VARCHAR(100)'),
(4, 'email', 'VARCHAR(50)');

-- --------------------------------------------------------

--
-- Структура таблиці `syst_defs_columns`
--

CREATE TABLE IF NOT EXISTS `syst_defs_columns` (
  `def_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) DEFAULT NULL,
  `field_name` varchar(45) DEFAULT NULL,
  `field_type` varchar(45) DEFAULT NULL,
  `field_caption` varchar(100) DEFAULT NULL,
  `field_read_level` smallint(6) DEFAULT NULL,
  `field_write_level` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`def_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=42 ;

--
-- Дамп даних таблиці `syst_defs_columns`
--

INSERT INTO `syst_defs_columns` (`def_id`, `table_name`, `field_name`, `field_type`, `field_caption`, `field_read_level`, `field_write_level`) VALUES
(37, 'public_articles_new_test', 'ant_id', 'id', NULL, 90, 90),
(3, 'public_main_menu', 'menu_item_name', 'edit', 'menu_item_name', 0, 50),
(4, 'public_main_menu', 'menu_item_link', 'edit', 'menu_item_link', 0, 50),
(5, 'public_main_menu', 'owner', 'id', 'owner', 80, 50),
(6, 'public_static_pages', 'static_page_id', 'id', 'id', 20, 50),
(7, 'public_static_pages', 'static_page_alias', 'edit', 'alias', 0, 50),
(8, 'public_static_pages', 'static_page_text', 'text', 'text', 0, 50),
(10, 'public_categories', 'cat_id', 'id', 'id', 0, 80),
(11, 'public_categories', 'cat_name', 'edit', 'name', 0, 80),
(12, 'public_categories', 'cat_alias', 'edit', 'alias', 0, 80),
(13, 'public_categories', 'cat_parent_id', 'id', 'parent_id', 0, 80),
(14, 'public_articles', 'article_id', 'id', 'id', 0, 50),
(15, 'public_articles', 'article_name', 'edit', 'name', 0, 50),
(16, 'public_articles', 'article_alias', 'edit', 'alias', 0, 50),
(17, 'public_articles', 'article_text', 'edit', 'text', 0, 50),
(18, 'hrefs_articles_to_categories', 'href_cat_id', 'id', 'cat_id', 0, 50),
(19, 'hrefs_articles_to_categories', 'href_article_id', 'id', 'article_id', 0, 50),
(20, 'public_sessions_data', 'sess_data_user_data', 'json', 'session_data', 0, 0),
(38, 'public_articles_new_test', 'ant_name', 'VARCHAR(100)', NULL, 40, 60),
(39, 'public_articles_new_test', 'ant_alias', 'VARCHAR(100)', NULL, 40, 60),
(40, 'public_articles_new_test', 'ant_email', 'email', NULL, 40, 60),
(41, 'public_articles_new_test', 'ant_data', 'json', NULL, 40, 60);

-- --------------------------------------------------------

--
-- Структура таблиці `syst_defs_tables`
--

CREATE TABLE IF NOT EXISTS `syst_defs_tables` (
  `tbl_def_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) NOT NULL,
  `table_read_level` int(11) NOT NULL,
  `table_write_level` int(11) NOT NULL,
  `table_insert_level` int(11) NOT NULL DEFAULT '0',
  `table_delete_level` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tbl_def_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Дамп даних таблиці `syst_defs_tables`
--

INSERT INTO `syst_defs_tables` (`tbl_def_id`, `table_name`, `table_read_level`, `table_write_level`, `table_insert_level`, `table_delete_level`) VALUES
(3, 'public_articles_new_test', 40, 60, 40, 60);

-- --------------------------------------------------------

--
-- Структура таблиці `syst_groups`
--

CREATE TABLE IF NOT EXISTS `syst_groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `group_read_level` int(11) NOT NULL,
  `group_write_level` int(11) NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Дамп даних таблиці `syst_groups`
--

INSERT INTO `syst_groups` (`group_id`, `group_name`, `group_read_level`, `group_write_level`) VALUES
(1, 'super_administrators', 100, 100),
(2, 'administrators', 90, 90),
(3, 'guests', 20, 20),
(4, 'users', 40, 40),
(5, 'clients', 60, 60),
(6, 'managers', 80, 80);

-- --------------------------------------------------------

--
-- Структура таблиці `syst_sessions`
--

CREATE TABLE IF NOT EXISTS `syst_sessions` (
  `sess_id` int(11) NOT NULL AUTO_INCREMENT,
  `sess_f_user_id` int(11) NOT NULL DEFAULT '0',
  `sess_user_token` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `sess_user_last_ip` varchar(15) NOT NULL,
  `sess_user_last_activity` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sess_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46 ;

--
-- Дамп даних таблиці `syst_sessions`
--

INSERT INTO `syst_sessions` (`sess_id`, `sess_f_user_id`, `sess_user_token`, `sess_user_last_ip`, `sess_user_last_activity`) VALUES
(9, 3, NULL, '127.0.0.2', '2012-10-08 13:29:45'),
(8, 1, '7c2c38ce79637667b107403155656836', '127.0.0.1', '2012-10-09 10:52:34'),
(20, 0, 'aee2ab83b4456f3bddabfa83b8b8332b', '127.0.0.1', '2012-11-02 11:02:36'),
(21, 0, '5637270448b4806a8a15e3b186394811', '127.0.0.1', '2013-04-01 13:45:51'),
(22, 0, '20caed8c2efd02a06ff2469c72feee17', '127.0.0.1', '2013-04-01 13:45:57'),
(23, 0, 'e598d041cbcbb369517544fd64e53331', '127.0.0.1', '2013-04-15 15:33:34'),
(24, 0, 'a969e39c162d3d787666a025d88e1d6b', '127.0.0.1', '2013-04-15 10:00:44'),
(25, 0, 'b111feed6ba72bc82170895515e36f65', '127.0.0.1', '2013-04-15 14:58:05'),
(26, 0, 'ae2a7f509e5f6034decbc3a5795f9079', '127.0.0.1', '2013-04-15 15:00:23'),
(27, 0, '73057150c94ec4b3fa1c5e9fa3a20266', '127.0.0.1', '2013-04-15 15:00:30'),
(28, 0, '54ad290258405e14263dddffc470b776', '127.0.0.1', '2013-04-15 15:00:59'),
(29, 0, 'af1fbaa0e72753bbfdbd42f96f3297b5', '127.0.0.1', '2013-04-15 15:02:25'),
(30, 0, '1162c68e7f4f3a4b0f9fa0231f5bdd3a', '127.0.0.1', '2013-04-15 15:07:59'),
(31, 0, '570c3cc1620b438c5a4934e7ed6ac08d', '127.0.0.1', '2013-04-15 15:08:10'),
(32, 0, 'b201cb27d5cdf22bbe699dc2b9bb57d4', '127.0.0.1', '2013-04-15 15:09:25'),
(33, 0, '030fae77fffa178fda70985ad4a776d4', '127.0.0.1', '2013-04-15 15:12:33'),
(34, 0, 'c3398efae4206c9525791b89acc3cb5b', '127.0.0.1', '2013-04-15 15:12:55'),
(35, 0, '6a74f9397bb8ef6cf865abe48442a9b8', '127.0.0.1', '2013-04-15 15:13:04'),
(36, 0, '28bef3b52f73de7098a2c425b63afe63', '127.0.0.1', '2013-04-15 15:13:19'),
(37, 0, '633da94e086e6db44a419180624702d4', '127.0.0.1', '2013-04-15 15:13:40'),
(38, 0, 'c2b38985631b2bf3e889e4396bc2b2e4', '127.0.0.1', '2013-04-15 15:15:37'),
(39, 0, '30d28051544bddced7ea003f31b3e0dd', '127.0.0.1', '2013-04-15 15:16:02'),
(40, 0, '592f401d713ea16e720afadb4b6647ff', '127.0.0.1', '2013-04-15 15:21:42'),
(41, 0, 'b0b61caf2e78e1adda580cf639b82acb', '127.0.0.1', '2013-04-15 15:22:35'),
(42, 0, '7d8d723216eca7133a829c42bc761b7a', '127.0.0.1', '2013-04-15 15:25:16'),
(43, 0, '22e6a0696f29dfa745d65eadad4dc2a0', '127.0.0.1', '2013-04-15 15:31:00'),
(44, 0, '150990bba5c3dc707b62f29fab396e98', '127.0.0.1', '2013-04-15 15:35:16'),
(45, 0, 'e96759f976b84e27621f2d8479716960', '127.0.0.1', '2013-04-17 12:40:29');

-- --------------------------------------------------------

--
-- Структура таблиці `syst_settings`
--

CREATE TABLE IF NOT EXISTS `syst_settings` (
  `sett_id` int(11) NOT NULL AUTO_INCREMENT,
  `sett_session_time` varchar(20) NOT NULL,
  `sett_user_token_time` varchar(20) NOT NULL,
  `sett_passw_salt` varchar(50) NOT NULL,
  PRIMARY KEY (`sett_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп даних таблиці `syst_settings`
--

INSERT INTO `syst_settings` (`sett_id`, `sett_session_time`, `sett_user_token_time`, `sett_passw_salt`) VALUES
(1, '1:0:0.0', '0:15:0.0', 'ababagalamaga');

-- --------------------------------------------------------

--
-- Структура таблиці `syst_users`
--

CREATE TABLE IF NOT EXISTS `syst_users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_login` varchar(150) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `user_pass` varchar(255) NOT NULL,
  `user_is_active` tinyint(1) NOT NULL DEFAULT '1',
  `user_f_group_id` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_login` (`user_login`,`user_email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Дамп даних таблиці `syst_users`
--

INSERT INTO `syst_users` (`user_id`, `user_login`, `user_email`, `user_pass`, `user_is_active`, `user_f_group_id`) VALUES
(1, 'admin', 'admin@admin.com', 'abLEFxdWWYR3c', 1, 1),
(2, 'user', 'user@user.com', 'abYKGh8dZINzc', 1, 3),
(3, 'test', 'test@mail', 'abgOeLfPimXQo', 1, 3);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
