<?php

declare(strict_types=1);

// Taken from https://github.com/akeneo/pim-community-dev/issues/14185#issuecomment-828188449
//
// It seems Akeneo folks just missed this needed change (adding job instances)
// Someone created a GH issue for a cron job failing and included the solution
// in the form of this migration.

namespace Pim\Upgrade\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version_5_0_20210426151505_add_dqi_jobs extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->createJobInstance('data_quality_insights_evaluations', 'Launch the evaluations of products and structure.');
        $this->createJobInstance('data_quality_insights_periodic_tasks', 'Schedule the periodic tasks of Data-Quality-Insights.');
    }

    private function createJobInstance(string $jobName, string $label)
    {
        $sql = <<<SQL
INSERT INTO akeneo_batch_job_instance (code, label, job_name, status, connector, raw_parameters, type)
VALUES (:code, :label, :job_name, :status, :connector, :raw_parameters, :type);
SQL;
        $this->addSql($sql, [
            'code'           => $jobName,
            'label'          => $label,
            'job_name'       => $jobName,
            'connector' => 'Data Quality Insights Connector',
            'status' => 0,
            'raw_parameters' => 'a:0:{}',
            'type' => 'data_quality_insights',
        ]);
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
