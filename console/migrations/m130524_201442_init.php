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

        $sql_create_table_seeds = [];
        $sql_create_table_seeds[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%seeds}} (
	seed_id 			SERIAL NOT NULL PRIMARY KEY,
	info_hash 			char(40) NOT NULL,
	torrent_name 		varchar(250) NOT NULL DEFAULT '未命名',
	torrent_size 		bigint NOT NULL DEFAULT 0,
	file_count 			int NOT NULL DEFAULT 0,
	seeder_count 		int NOT NULL DEFAULT 0,
	leecher_count 		int NOT NULL DEFAULT 0,
	completed_count 	int NOT NULL DEFAULT 0,
	last_active_time 	timestamp NOT NULL DEFAULT now(),
	is_valid 			boolean NOT NULL DEFAULT true,
	pub_time 			timestamp NOT NULL DEFAULT now(),
	traffic				bigint NOT NULL DEFAULT 0,
	up_coef				real NOT NULL DEFAULT 1,
	down_coef			real NOT NULL DEFAULT 1,
	coef_expire_time	timestamp NOT NULL DEFAULT 'infinity',
	live_time 			int NOT NULL DEFAULT 0,
	create_time			timestamp NOT NULL DEFAULT now(),
	update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        $sql_create_table_seeds[] = <<<SQL
CREATE UNIQUE INDEX idx_info_hash ON {{%seeds}}(info_hash);
SQL;
        array_map($execute_sql, $sql_create_table_seeds);

//========================================================================

        $sql_create_table_users = [];
        $sql_create_table_users[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%users}} (
	user_id 			SERIAL NOT NULL PRIMARY KEY,
	discuz_user_id		int NOT NULL,
	passkey 			char(32) NOT NULL,
	stat_up 			bigint NOT NULL DEFAULT 0,
	stat_down 			bigint NOT NULL DEFAULT 0,
	real_up 			bigint NOT NULL DEFAULT 0,
	real_down 			bigint NOT NULL DEFAULT 0,
	is_valid 			boolean NOT NULL DEFAULT true,
	create_time			timestamp NOT NULL DEFAULT now(),
	update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        $sql_create_table_users[] = <<<SQL
CREATE UNIQUE INDEX idx_discuz_user_id ON {{%users}}(discuz_user_id);
SQL;
        array_map($execute_sql, $sql_create_table_users);

//========================================================================

        $sql_create_table_historys = array();
        $sql_create_table_historys[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%historys}} (
	histroy_id 			BIGSERIAL NOT NULL PRIMARY KEY,
	user_id				int NOT NULL REFERENCES {{%users}}(user_id),
	seed_id				int NOT NULL REFERENCES {{%seeds}}(seed_id),
	stat_up 			bigint NOT NULL DEFAULT 0,
	stat_down 			bigint NOT NULL DEFAULT 0,
	real_up 			bigint NOT NULL DEFAULT 0,
	real_down 			bigint NOT NULL DEFAULT 0,
	record_date			date NOT NULL DEFAULT now(),
	create_time			timestamp NOT NULL DEFAULT now(),
	update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        array_map($execute_sql, $sql_create_table_historys);

//========================================================================

        $sql_create_table_peers = array();

        $sql_create_table_peers[] = <<<SQL
CREATE TYPE peer_type AS ENUM ('Seeder','Leecher');
SQL;

        $sql_create_table_peers[] = <<<SQL
CREATE TABLE IF NOT EXISTS {{%peers}} (
	peers_id			SERIAL PRIMARY KEY,
	user_id				int NOT NULL REFERENCES {{%users}}(user_id),
	seed_id				int NOT NULL REFERENCES {{%seeds}}(seed_id),
	real_up 			bigint NOT NULL DEFAULT 0,
	real_down 			bigint NOT NULL DEFAULT 0,
	ipv4_addr			varchar(17),
	ipv4_port			int,
	ipv6_addr			varchar(45),
	ipv6_port			int,	
	client_tag			varchar(60),
	status				peer_type NOT NULL DEFAULT 'Seeder',
	create_time			timestamp NOT NULL DEFAULT now(),
	update_time 		timestamp NOT NULL DEFAULT now()
);
SQL;
        $sql_create_table_peers[] = <<<SQL
CREATE INDEX idx_peers_user_seed ON {{%peers}}(user_id,seed_id);
SQL;
        array_map($execute_sql, $sql_create_table_peers);

//========================================================================
    }

    public function down()
    {
        $this->dropTable('{{%peers}}');
        $this->dropTable('{{%historys}}');
        $this->dropTable('{{%users}}');
        $this->dropTable('{{%seeds}}');
        $sqls = <<<SQL
DROP TYPE peer_type;
SQL;
        Yii::$app->db->createCommand($sqls)->execute();
        //$this->dropIndex('idx_info_hash');
        //$this->dropIndex('idx_discuz_user_id');
        //$this->dropIndex('idx_peers_user_seed');
    }
}
