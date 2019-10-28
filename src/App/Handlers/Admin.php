<?php
/**
 * Basic administrative UI.
 **/

namespace App\Handlers;

use Slim\Http\Request;
use Slim\Http\Response;

class Admin extends \Ufw1\Handlers\Admin
{
    /**
     * Delete or undelete a node.
     **/
    public function onDeleteNode(Request $request, Response $response, array $args)
    {
        $this->db->beginTransaction();

        $user = $this->requireUser($request);

        $id = (int)$request->getParam('id');
        $deleted = (int)$request->getParam('deleted');

        if (!($node = $this->node->get($id)))
            $this->fail('Документ не найден.');

        // Check access.
        $config = $this->getNodeConfig($node['type']);
        if ($user['role'] != 'admin' and (empty($config['edit_roles']) or !in_array($user['role'], $config['edit_roles'])))
            $this->forbidden();

        $node['deleted'] = $deleted;
        $node = $this->node->save($node);

        if ($node['type'] == 'user')
            $this->taskq('export-all');

        $this->db->commit();

        if ($node['type'] == 'user' and $deleted)
            $message = 'Пользователь удалён.';
        elseif ($node['type'] == 'user' and !$deleted)
            $message = 'Пользователь восстановлен.';
        elseif ($deleted)
            $message = 'Документ удалён';
        elseif (!$deleted)
            $message = 'Документ восстановлен.';

        return $response->withJSON([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Publish or unpublish a node.
     **/
    public function onPublishNode(Request $request, Response $response, array $args)
    {
        $this->db->beginTransaction();

        $user = $this->requireUser($request);

        $id = (int)$request->getParam('id');
        $published = (int)$request->getParam('published');

        if (!($node = $this->node->get($id)))
            $this->fail('Документ не найден.');

        // Check access.
        $config = $this->getNodeConfig($node['type']);
        if ($user['role'] != 'admin' and (empty($config['edit_roles']) or !in_array($user['role'], $config['edit_roles'])))
            $this->forbidden();

        $node['published'] = $published;
        $node = $this->node->save($node);

        if ($node['type'] == 'user')
            $this->taskq('export-all');

        $this->db->commit();

        if ($node['type'] == 'user' and $published)
            $message = 'Пользователь активирован.';
        elseif ($node['type'] == 'user' and !$published)
            $message = 'Пользователь заблокирован.';
        elseif ($published)
            $message = 'Документ опубликован';
        elseif (!$published)
            $message = 'Документ сокрыт.';

        return $response->withJSON([
            'success' => true,
            'message' => $message,
        ]);
    }
}
