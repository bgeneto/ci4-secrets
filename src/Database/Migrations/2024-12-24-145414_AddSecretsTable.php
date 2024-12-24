<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSecretsTable extends Migration
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
            'key_name' => [
                'type' => 'VARCHAR',
                'constraint' => 254,
                'unique' => true,
            ],
            'encrypted_value' => [
                'type' => 'TEXT',
            ],
        ]);
        $this->forge->addField('created_at DATETIME NOT NULL DEFAULT current_timestamp()');
        $this->forge->addField('updated_at DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()');
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('secrets');
    }

    public function down()
    {
        $this->forge->dropTable('secrets');
    }
}
