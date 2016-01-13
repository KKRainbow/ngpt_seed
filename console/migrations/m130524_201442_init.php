<?php

use yii\db\Schema;
use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $execute_sql = function ($sql) {
            $db = Yii::$app->db;
            $db->createCommand($sql)->execute();
        };
//========================================================================

        $sql_create_table_user = [];
        $sql_create_table_user[] = <<<SQL
CREATE TYPE user_priv AS ENUM ('Admin','User','Anonymous');
SQL;
        $sql_create_table_user[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%user}} (
    user_id 			SERIAL NOT NULL PRIMARY KEY,
    discuz_user_id		int NOT NULL,
    passkey 			char(32) NOT NULL,
    priv                user_priv NOT NULL DEFAULT 'User',
    stat_up 			bigint NOT NULL DEFAULT 0,
    stat_down 			bigint NOT NULL DEFAULT 0,
    real_up 			bigint NOT NULL DEFAULT 0,
    real_down 			bigint NOT NULL DEFAULT 0,
    extra_up_coef       smallint NOT NULL DEFAULT 100,
    extra_down_coef     smallint NOT NULL DEFAULT 100,
    extra_coef_expire   int NOT NULL DEFAULT 0,
    is_valid 			boolean NOT NULL DEFAULT true,
    create_time			timestamp NOT NULL DEFAULT now(),
    update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        $sql_create_table_user[] = <<<SQL
CREATE UNIQUE INDEX idx_discuz_user_id ON {{%user}}(discuz_user_id);
SQL;

        $sql_create_table_user[] = <<<SQL
CREATE UNIQUE INDEX idx_passkey ON {{%user}}(passkey);
SQL;
        array_map($execute_sql, $sql_create_table_user);

//========================================================================

        $sql_create_table_seed = [];
        $sql_create_table_seed[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%seed}} (
    seed_id 			SERIAL NOT NULL PRIMARY KEY,
    type_id             int NOT NULL DEFAULT -1,
    sub_type_id         int NOT NULL DEFAULT -1,
    info_hash 			char(40) NOT NULL,
    source_str          char(50) NOT NULL DEFAULT '',
    torrent_name 		varchar(250) NOT NULL DEFAULT '未命名',
    full_name 		    varchar(250) NOT NULL DEFAULT '未命名',
    file_size    		bigint NOT NULL DEFAULT 0,
    file_count 			int NOT NULL DEFAULT 0,
    seeder_count 		int NOT NULL DEFAULT 0,
    leecher_count 		int NOT NULL DEFAULT 0,
    completed_count 	int NOT NULL DEFAULT 0,
    last_active_time 	timestamp NOT NULL DEFAULT now(),
    is_valid 			boolean NOT NULL DEFAULT true,
    pub_time 			timestamp NOT NULL DEFAULT now(),
    traffic_up			bigint NOT NULL DEFAULT 0,
    traffic_down		bigint NOT NULL DEFAULT 0,
    coefs_stack         int[][] NOT NULL DEFAULT '{{100,100,0}}',
    live_time 			int NOT NULL DEFAULT 0,
    publisher_user_id   int NOT NULL REFERENCES {{%user}}(user_id),
    detail_info         json,
    create_time			timestamp NOT NULL DEFAULT now(),
    update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        $sql_create_table_seed[] = <<<SQL
CREATE UNIQUE INDEX idx_info_hash ON {{%seed}}(info_hash);
SQL;
        array_map($execute_sql, $sql_create_table_seed);

//========================================================================

        $sql_create_table_history = array();
        $sql_create_table_history[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%history}} (
    history_id 			BIGSERIAL NOT NULL PRIMARY KEY,
    user_id				int NOT NULL REFERENCES {{%user}}(user_id),
    seed_id				int NOT NULL REFERENCES {{%seed}}(seed_id),
    stat_up 			bigint NOT NULL DEFAULT 0,
    stat_down 			bigint NOT NULL DEFAULT 0,
    real_up 			bigint NOT NULL DEFAULT 0,
    real_down 			bigint NOT NULL DEFAULT 0,
    record_date			date NOT NULL DEFAULT now(),
    create_time			timestamp NOT NULL DEFAULT now(),
    update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        array_map($execute_sql, $sql_create_table_history);

//========================================================================

        $sql_create_table_peer_lifecycle = array();

        $sql_create_table_peer_lifecycle[] = <<<SQL
CREATE TYPE peer_type AS ENUM ('Seeder','Leecher');
SQL;

        $sql_create_table_peer_lifecycle[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%peer_lifecycle}} (
    record_id  			BIGSERIAL NOT NULL PRIMARY KEY,
    user_id 			int NOT NULL REFERENCES {{%user}}(user_id),
    seed_id				int NOT NULL REFERENCES {{%seed}}(seed_id),
    ipv4		        inet,
    ipv6                inet,
    status              peer_type NOT NULL,
    client_tag			varchar(60),
    begin_time			timestamp NOT NULL DEFAULT now(),
    end_time			timestamp NOT NULL DEFAULT now()
);
SQL;
        $sql_create_table_peer_lifecycle[] = <<<SQL
CREATE INDEX idx_peer_lifecycle_id ON {{%peer_lifecycle}}(user_id);
SQL;
        $sql_create_table_peer_lifecycle[] = <<<SQL
CREATE INDEX idx_peer_lifecycle_seed_id ON {{%peer_lifecycle}}(seed_id);
SQL;
        array_map($execute_sql, $sql_create_table_peer_lifecycle);

//========================================================================

        $sql_create_table_peer = array();

        $sql_create_table_peer[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%peer}} (
    peer_id 			SERIAL PRIMARY KEY,
    user_id				int NOT NULL REFERENCES {{%user}}(user_id),
    seed_id				int NOT NULL REFERENCES {{%seed}}(seed_id),
    lifecycle_id        bigint REFERENCES{{%peer_lifecycle}}(record_id),
    real_up 			bigint NOT NULL DEFAULT 0,
    real_down 			bigint NOT NULL DEFAULT 0,
    ipv4_addr			varchar(17),
    ipv4_port			int,
    ipv6_addr			varchar(45),
    ipv6_port			int,
    up_coef             smallint NOT NULL DEFAULT 100,
    down_coef           smallint NOT NULL DEFAULT 100,
    client_tag			varchar(60),
    status				peer_type NOT NULL DEFAULT 'Seeder',
    create_time			timestamp NOT NULL DEFAULT now(),
    update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        $sql_create_table_peer[] = <<<SQL
CREATE INDEX idx_peer_user_seed ON {{%peer}}(user_id,seed_id);
SQL;
        $sql_create_table_peer[] = <<<SQL
SQL;
        array_map($execute_sql, $sql_create_table_peer);

//========================================================================

        $sql_create_table_seed_operation_record = array();
        $sql_create_table_seed_operation_record[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%seed_operation_record}} (
    record_id  			BIGSERIAL NOT NULL PRIMARY KEY,
    admin_id			int NOT NULL REFERENCES {{%user}}(user_id),
    seed_id				int NOT NULL REFERENCES {{%seed}}(seed_id),
    publisher_id		int NOT NULL REFERENCES {{%user}}(user_id),
    operation_type      char(20) NOT NULL DEFAULT 'NOT_RECORDED',
    detail_info         json,
    create_time			timestamp NOT NULL DEFAULT now(),
    update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        array_map($execute_sql, $sql_create_table_seed_operation_record);

//========================================================================

        $sql_create_table_seed_event = array();

        $sql_create_table_seed_event[] = <<<SQL
CREATE TYPE seed_event_type AS ENUM ('Downloaded','Completed');
SQL;

        $sql_create_table_seed_event[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%seed_event}} (
    record_id  			BIGSERIAL NOT NULL PRIMARY KEY,
    seed_id				int NOT NULL REFERENCES {{%seed}}(seed_id),
    user_id				int NOT NULL REFERENCES {{%user}}(user_id),
    event_type          seed_event_type NOT NULL,
    create_time			timestamp NOT NULL DEFAULT now()
);
SQL;
        $sql_create_table_seed_event[] = <<<SQL
CREATE INDEX idx_seed_event_user_id ON {{%seed_event}}(user_id);
SQL;
        $sql_create_table_seed_event[] = <<<SQL
CREATE INDEX idx_seed_event_seed_id ON {{%seed_event}}(seed_id);
SQL;
        array_map($execute_sql, $sql_create_table_seed_event);

//========================================================================

        $create_trigger_peer_update = array();
        $create_trigger_peer_update[] = <<<SQL
CREATE OR REPLACE FUNCTION
  after_peer_statistic_change_trigger()
  RETURNS TRIGGER AS $$
DECLARE
  up_diff BIGINT := 0;
  down_diff BIGINT := 0;
  up_res BIGINT := 0;
  down_res BIGINT := 0;
  up_coef integer := 1;
  down_coef integer := 1;
  peer_up_coef integer := 1;
  peer_down_coef integer := 1;
  extra_up_coef integer := 1;
  extra_down_coef integer := 1;
  stat_up_diff BIGINT := 0;
  stat_down_diff BIGINT := 0;
  live_time_diff INT :=0;
BEGIN
  SELECT coefs_stack[1][1],coefs_stack[1][2] INTO
    up_coef,down_coef FROM {{%seed}} WHERE seed_id=NEW.seed_id;
  SELECT {{%user}}.extra_up_coef,{{%user}}.extra_down_coef INTO
    extra_up_coef,extra_down_coef FROM {{%user}} WHERE user_id=NEW.user_id;
  peer_up_coef := NEW.up_coef;
  peer_down_coef := NEW.down_coef;
  IF NOT EXISTS( SELECT * FROM {{%history}} WHERE
    seed_id=NEW.seed_id AND user_id=NEW.user_id AND record_date='today')
  THEN
    INSERT INTO {{%history}}(user_id,seed_id) VALUES(NEW.user_id,NEW.seed_id);
  END IF;
  SELECT NEW.real_up-OLD.real_up,NEW.real_down-OLD.real_down INTO up_diff,down_diff;
  IF up_diff<0
  THEN
    up_diff := 0;
  END IF;
  IF down_diff<0
  THEN
    down_diff := 0;
  END IF;
  IF NEW.status='Seeder' AND OLD.status='Seeder'
  THEN
    live_time_diff := cast(extract(EPOCH from NEW.update_time-OLD.update_time) as INTEGER);
  END IF;
  SELECT (up_diff*up_coef)/100,(down_diff*down_coef)/100
  INTO stat_up_diff,stat_down_diff;
  SELECT (stat_up_diff*extra_up_coef)/100,(stat_down_diff*extra_down_coef)/100
  INTO stat_up_diff,stat_down_diff;
  SELECT (stat_up_diff*peer_up_coef)/100,(stat_down_diff*peer_down_coef)/100
  INTO stat_up_diff,stat_down_diff;
  --开始更新所有有关的信息了
  UPDATE {{%history}} SET
    real_up=real_up+up_diff,
    real_down=real_down+down_diff,
    stat_up=stat_up+stat_up_diff,
    stat_down=stat_down+stat_down_diff
  WHERE
    seed_id=NEW.seed_id AND user_id=NEW.user_id AND record_date='today';
  UPDATE {{%user}} SET
    real_up=real_up+up_diff,
    real_down=real_down+down_diff,
    stat_up=stat_up+stat_up_diff,
    stat_down=stat_down+stat_down_diff
  WHERE
    user_id=NEW.user_id;
  UPDATE {{%seed}} SET
    traffic_up=traffic_up+up_diff,
    traffic_down=traffic_down+down_diff,
    live_time=live_time+live_time_diff
  WHERE
    seed_id=NEW.seed_id;

  IF NEW.status='Seeder'
  THEN
      UPDATE {{%seed}} SET
        last_active_time=now()
      WHERE
        seed_id=NEW.seed_id;
  END IF;

  RETURN NULL;

END;
$$
LANGUAGE plpgsql;
SQL;

        $create_trigger_peer_update[] = <<<SQL
CREATE OR REPLACE FUNCTION
  after_peer_deleted_trigger()
  RETURNS TRIGGER AS $$
DECLARE
  new_status peer_type;
BEGIN
  case OLD.status
    WHEN 'Seeder' THEN
    UPDATE {{%seed}} SET seeder_count=seeder_count-1 WHERE seed_id=OLD.seed_id;
    WHEN 'Leecher' THEN
    UPDATE {{%seed}} SET leecher_count=leecher_count-1 WHERE seed_id=OLD.seed_id;
  END CASE;
  UPDATE {{%peer_lifecycle}} set end_time=now() WHERE record_id=OLD.lifecycle_id;
  RETURN NULL;
END;
$$
LANGUAGE plpgsql;
SQL;

        $create_trigger_peer_update[] = <<<SQL
CREATE OR REPLACE FUNCTION
  after_peer_status_change_trigger()
  RETURNS TRIGGER AS $$
DECLARE
  new_status peer_type;
BEGIN
  case OLD.status
    WHEN 'Seeder' THEN
    UPDATE {{%seed}} SET seeder_count=seeder_count-1 WHERE seed_id=NEW.seed_id;
    WHEN 'Leecher' THEN
    UPDATE {{%seed}} SET leecher_count=leecher_count-1 WHERE seed_id=NEW.seed_id;
  END CASE;
  case NEW.status
    WHEN 'Seeder' THEN
    UPDATE {{%seed}} SET seeder_count=seeder_count+1 WHERE seed_id=NEW.seed_id;
    WHEN 'Leecher' THEN
    UPDATE {{%seed}} SET leecher_count=leecher_count+1 WHERE seed_id=NEW.seed_id;
  END CASE;
  IF NEW.status != OLD.status
  THEN
      UPDATE {{%peer_lifecycle}} SET end_time=now() WHERE record_id=OLD.lifecycle_id;
      INSERT INTO {{%peer_lifecycle}}(user_id,seed_id,ipv4,ipv6,status,client_tag)
        VALUES(NEW.user_id,NEW.seed_id,cast(NEW.ipv4_addr as inet),
        cast(NEW.ipv6_addr as inet),NEW.status,NEW.client_tag);
      NEW.lifecycle_id = currval('peer_lifecycle_record_id_seq');
  END IF;
  RETURN NEW;
END;
$$
LANGUAGE plpgsql;
SQL;

        $create_trigger_peer_update[] = <<<SQL
CREATE OR REPLACE FUNCTION
  after_peer_inserted_trigger()
  RETURNS TRIGGER AS $$
DECLARE
  new_status peer_type;
BEGIN
  case NEW.status
    WHEN 'Seeder' THEN
    UPDATE {{%seed}} SET seeder_count=seeder_count+1 WHERE seed_id=NEW.seed_id;
    WHEN 'Leecher' THEN
    UPDATE {{%seed}} SET leecher_count=leecher_count+1 WHERE seed_id=NEW.seed_id;
  END CASE;
  INSERT INTO {{%peer_lifecycle}}(user_id,seed_id,ipv4,ipv6,status,client_tag)
    VALUES(NEW.user_id,NEW.seed_id,cast(NEW.ipv4_addr as inet),
    cast(NEW.ipv6_addr as inet),NEW.status,NEW.client_tag);
  NEW.lifecycle_id = currval('peer_lifecycle_record_id_seq');
  RETURN NEW;
END;
$$
LANGUAGE plpgsql;
SQL;
        $create_trigger_peer_update[] = <<<SQL
CREATE TRIGGER after_peer_statistic_change_trigger_name
AFTER UPDATE ON {{%peer}}
FOR EACH ROW EXECUTE PROCEDURE after_peer_statistic_change_trigger();
SQL;

        $create_trigger_peer_update[] = <<<SQL
CREATE TRIGGER after_peer_status_change_trigger_name
BEFORE UPDATE ON {{%peer}}
FOR EACH ROW EXECUTE PROCEDURE after_peer_status_change_trigger();
SQL;

        $create_trigger_peer_update[] = <<<SQL
CREATE TRIGGER after_peer_inserted_change_trigger_name
BEFORE INSERT ON {{%peer}}
FOR EACH ROW EXECUTE PROCEDURE after_peer_inserted_trigger();
SQL;

        $create_trigger_peer_update[] = <<<SQL
CREATE TRIGGER after_peer_deleted_change_trigger_name
AFTER DELETE ON {{%peer}}
FOR EACH ROW EXECUTE PROCEDURE after_peer_deleted_trigger();
SQL;
        array_map($execute_sql, $create_trigger_peer_update);
        //after触发器会忽略对NEW的修改。所以需要两个BEFORE

//========================================================================

    }

    public function down()
    {
        $this->dropTable('{{%seed_event}}');
        $this->dropTable('{{%peer}}');
        $this->dropTable('{{%peer_lifecycle}}');
        $this->dropTable('{{%history}}');
        $this->dropTable('{{%seed_operation_record}}');
        $this->dropTable('{{%seed}}');
        $this->dropTable('{{%user}}');
        $sqls = [
            "DROP TYPE IF EXISTS peer_type;",
            "DROP TYPE IF EXISTS user_priv;",
            "DROP TYPE IF EXISTS seed_event_type;",
            "DROP TRIGGER IF EXISTS after_peer_statistic_change_trigger_name ON {{%peer}};",
            "DROP TRIGGER IF EXISTS after_peer_status_change_trigger_name ON {{%peer}};",
            "DROP TRIGGER IF EXISTS after_peer_inserted_change_trigger_name ON {{%peer}};",
            "DROP TRIGGER IF EXISTS after_peer_deleted_change_trigger_name ON {{%peer}};",
            "DROP FUNCTION IF EXISTS public.after_peer_deleted_trigger();",
            "DROP FUNCTION IF EXISTS public.after_peer_inserted_trigger();",
            "DROP FUNCTION IF EXISTS public.after_peer_statistic_change_trigger();",
            "DROP FUNCTION IF EXISTS public.after_peer_status_change_trigger();",
        ];
        array_map(function ($sql) {
            Yii::$app->db->createCommand($sql)->execute();
        }, $sqls);
    }
}
