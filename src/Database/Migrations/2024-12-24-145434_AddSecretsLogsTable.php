<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSecretsLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'key_name' => [
                'type' => 'VARCHAR',
                'constraint' => 254,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
            ],
        ]);
        $this->forge->addField('created_at DATETIME NOT NULL DEFAULT current_timestamp()');
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('secrets_logs');
    }

    public function down()
    {
        $this->forge->dropTable('secrets_logs');
    }
}
