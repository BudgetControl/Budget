<?php
namespace Budgetcontrol\Budget\Domain\Repository;

use Illuminate\Database\Capsule\Manager as DB;

class Repository {

    /**
     * Retrieves the tags associated with a specific entry.
     *
     * @param int $entryId The ID of the entry.
     * @return array The array of tags associated with the entry.
     */
    protected function tags(int $entryId): array
    {
        $query = "select tags.*, pe_tags.planned_entry_id as entry_id from labels as tags
        right join planned_entry_labels as pe_tags on tags.id = pe_tags.labels_id
        where pe_tags.planned_entry_id = $entryId AND tags.deleted_at IS NULL;";
        $results = DB::select($query);

        if(empty($results)) {
            return [];
        }

        return $results;
    }

    /**
     * Retrieves entries from the repository based on the given tags.
     *
     * @param array $tags The tags to filter the entries by.
     * @return array The array of entries matching the given tags.
     */
    protected function entriesFromTags(array $tags): array
    {
        $query = "select entries.* from entries
        inner join entry_labels on entries.id = entry_labels.entry_id
        inner join labels on entry_labels.labels_id = labels.id
        where labels.id in (".implode(',', $tags).") AND entries.deleted_at IS NULL;";
        $results = DB::select($query);

        if(count($results) === 0) {
            return [];
        }

        return $results;
    }

}