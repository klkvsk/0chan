<template>
    <div class="btn-group">
        <button v-if="board.canBeModerated || board.canBeManaged" class="btn btn-default" data-toggle="dropdown">
            <i class="fa fa-eye"></i>
        </button>

        <ul class="dropdown-menu pull-right">
            <li v-if="board.canBeModerated" class="dropdown-header">Модерация доски</li>
            <li v-if="board.canBeModerated">
                <router-link :to="{ name: 'mod_feed', params: { dir: board.dir } }">
                    <i class="fa fa-film fa-fw"></i> Фид картинок
                </router-link>
            </li>
            <li v-if="board.canBeModerated">
                <router-link :to="{ name: 'mod_reports', params: { dir: board.dir } }">
                    <i class="fa fa-flag-o fa-fw"></i> Жалобы
                </router-link>
            </li>
            <li v-if="board.canBeModerated">
                <router-link :to="{ name: 'mod_bans', params: { dir: board.dir } }">
                    <i class="fa fa-gavel fa-fw"></i> Баны
                </router-link>
            </li>
            <li v-if="board.canBeModerated">
                <router-link :to="{ name: 'mod_logs', params: { dir: board.dir } }">
                    <i class="fa fa-history fa-fw"></i> Логи
                </router-link>
            </li>
            <li v-if="board.canBeModerated">
                <router-link :to="{ name: 'mod_stats', params: { dir: board.dir } }">
                    <i class="fa fa-bar-chart fa-fw"></i> Статистика
                </router-link>
            </li>

            <li v-if="board.canBeManaged && board.canBeModerated" role="separator" class="divider"></li>
            <li v-if="board.canBeManaged" class="dropdown-header">Управление доской</li>
            <li v-if="board.canBeManaged" ><router-link :to="{ name: 'admin_mods', params: { dir: board.dir } }">
                <i class="fa fa-users fa-fw"></i> Модераторы
            </router-link></li>
            <li v-if="board.canBeManaged" ><router-link :to="{ name: 'admin_editBoard', params: { dir: board.dir } }">
                <i class="fa fa-edit fa-fw"></i> Параметры
            </router-link></li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: [ 'board' ]
    }
</script>