<?php

namespace Tests\Unit;

use App\Services\WorkflowBoard;
use PHPUnit\Framework\TestCase;

class WorkflowBoardTest extends TestCase
{
    public function test_empty_default_stages_remain_visible_and_match_management_list(): void
    {
        $board = new WorkflowBoard();
        $stages = collect([(object) ['id' => 8, 'name' => 'Failed', 'is_active' => true]]);
        [$columns, $groups] = $board->group(collect([
            (object) ['id' => 1, 'status' => 'completed', 'current_stage_id' => null],
        ]), $stages);

        $this->assertSame(['pending', 'in_progress', 'completed', 'stage_8'], array_column($columns, 'key'));
        $this->assertCount(0, $groups['in_progress']);
        $this->assertSame([1], $groups['completed']->pluck('id')->all());
        $this->assertSame(['pending', 'in_progress', 'completed'], array_column($board->defaultStages($stages), 'key'));

        [$emptyColumns] = $board->group(collect(), $stages);
        $this->assertSame($columns, $emptyColumns);
    }

    public function test_named_completed_stage_is_not_duplicated_in_management_list(): void
    {
        $defaults = (new WorkflowBoard())->defaultStages(collect([
            (object) ['id' => 8, 'name' => 'Completed', 'is_active' => true],
        ]));
        $this->assertSame(['pending', 'in_progress'], array_column($defaults, 'key'));
    }

    public function test_new_first_and_last_stages_do_not_move_assigned_records(): void
    {
        $board = new WorkflowBoard();
        $records = collect([
            (object) ['id' => 1, 'status' => 'completed', 'current_stage_id' => 12],
            (object) ['id' => 2, 'status' => 'in_progress', 'current_stage_id' => 11],
        ]);
        $stages = collect([
            (object) ['id' => 11, 'name' => 'Review'],
            (object) ['id' => 12, 'name' => 'Approved'],
        ]);

        $stages->prepend((object) ['id' => 13, 'name' => 'New first stage']);
        $stages->push((object) ['id' => 14, 'name' => 'New last stage']);
        [, $groups] = $board->group($records, $stages);

        $this->assertSame([1], $groups['stage_12']->pluck('id')->all());
        $this->assertSame([2], $groups['stage_11']->pluck('id')->all());
        $this->assertCount(0, $groups['stage_13']);
        $this->assertCount(0, $groups['stage_14']);
    }

    public function test_first_custom_stage_does_not_absorb_legacy_records(): void
    {
        $records = collect([
            (object) ['id' => 1, 'status' => 'pending', 'current_stage_id' => null],
            (object) ['id' => 2, 'status' => 'in_progress', 'current_stage_id' => null],
            (object) ['id' => 3, 'status' => 'completed', 'current_stage_id' => null],
        ]);
        $board = new WorkflowBoard();
        [$beforeColumns, $beforeGroups] = $board->group($records, collect());
        [$afterColumns, $afterGroups] = $board->group($records, collect([
            (object) ['id' => 1, 'name' => 'Additional checks'],
        ]));

        foreach ($beforeColumns as $column) {
            $this->assertSame(
                $beforeGroups[$column['key']]->pluck('id')->all(),
                $afterGroups[$column['key']]->pluck('id')->all()
            );
        }
        $this->assertCount(4, $afterColumns);
        $this->assertCount(0, $afterGroups['stage_1']);
    }

    public function test_legacy_completed_records_stay_in_named_completed_stage_when_appending(): void
    {
        $records = collect([(object) ['id' => 1, 'status' => 'completed', 'current_stage_id' => null]]);
        [, $groups] = (new WorkflowBoard())->group($records, collect([
            (object) ['id' => 1, 'name' => 'In Progress'],
            (object) ['id' => 2, 'name' => 'Completed'],
            (object) ['id' => 3, 'name' => 'New stage'],
        ]));

        $this->assertSame([1], $groups['stage_2']->pluck('id')->all());
        $this->assertCount(0, $groups['stage_3']);
    }

    public function test_missing_stage_does_not_hide_records(): void
    {
        $records = collect([(object) ['id' => 1, 'status' => 'in_progress', 'current_stage_id' => 99]]);
        [$columns, $groups] = (new WorkflowBoard())->group($records, collect([
            (object) ['id' => 1, 'name' => 'Review'],
        ]));

        $this->assertContains('in_progress', array_column($columns, 'key'));
        $this->assertSame([1], $groups['in_progress']->pluck('id')->all());
    }
}
