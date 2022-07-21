<?php

use Typecho\Db;
use Typecho\Plugin;
use Typecho\Plugin\Exception;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Archive;
use Widget\Contents\Post\Admin;
use Widget\Contents\Post\Edit;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Enable Posts Order
 *
 * @package EnablePostsOrder
 * @author Light
 * @version 1.0.0
 * @link https://github.com/LightAPIs
 */

class EnablePostsOrder_Plugin implements PluginInterface {
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate() {
        //! 将 posts 结果按 order 排序
        Archive::pluginHandle()->categoryHandle = __CLASS__ . '::orderPosts';
        // 文章编辑页面添加排序输入框
        Plugin::factory('admin/write-post.php')->option = __CLASS__ . '::writePostRender';
        // 修改文章编辑页面的提交结果
        Edit::pluginHandle()->write = __CLASS__ . '::modifyPostContents';
        // 为 manage-posts.php 补充显示 order 值
        Plugin::factory('admin/footer.php')->end = __CLASS__ . '::managePostsAddOrder';
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate() {
        // do nothing
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        /** 默认排序值 */
        $order = new Text('order', null, '0', _t('新增文章的默认排序值'), _t('数字越大文章排序就越靠前。'));
        $form->addInput($order);
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
        // do nothing
    }

    /**
     * 文章编辑页面添加排序输入框的方法
     *
     * @param $post
     * @return void
     * @throws Exception
     */
    public static function writePostRender($post)
    {
        $defaultOrder = Options::alloc()->plugin('EnablePostsOrder')->order;
        $order = $post->order ?? $defaultOrder;
        echo '<section class="typecho-post-option"><label for="order" class="typecho-label">排序</label>'
            . '<p><input id="order" name="order" type="text" value="' . $order . '" class="w-100 text"/></p>'
            . '<p class="description">文章排序，默认为 ' . $defaultOrder . '，数字越大文章排序就越靠前。</p></section>';
    }

    /**
     * 将文章查询结果按 order 排序的方法
     *
     * @param $that
     * @param $select
     * @return void
     */
    public static function orderPosts($that, $select) {
        $select->order('table.contents.order', Db::SORT_DESC)->order('table.contents.created');
    }

    /**
     * 修改文章的提交结果
     *
     * @param $contents
     * @param $that
     * @return mixed
     * @throws Exception
     */
    public static function modifyPostContents($contents, $that) {
        $defaultOrder = Options::alloc()->plugin('EnablePostsOrder')->order;
        $contents['order'] = $that->request->get('order', _t($defaultOrder));
        return $contents;
    }

    /**
     * 为 manage-posts.php 补充显示 order 值的方法
     * - 注：Typecho 1.2.0+ 中 manage-posts.php 页面与之前不同，此处只针对 Typecho 1.2.0+ 进行处理
     *
     * @return void
     */
    public static function managePostsAddOrder() {
        if (strpos($_SERVER['REQUEST_URI'], '/manage-posts.php')) {
            $posts = Admin::alloc();
            $orderArr = [];
            if ($posts->have()) {
                while ($posts->next()) {
                    $orderArr[] = $posts->order;
                }
            }
            $orderListStr = implode(",", $orderArr);
            if (strlen($orderListStr) > 0) {
                $pl = _t('评论');
                $px = _t('排序');
                $bt = _t('标题');
                $zz = _t('作者');
                $fl = _t('分类');
                $rq = _t('日期');
                echo <<<EOD
<script type="text/javascript">
(function () {
    const orderList = [$orderListStr];
    const listTable = document.querySelector(".operate-form .typecho-table-wrap .typecho-list-table");
    if (listTable) {
        const colgroupCol2 = listTable.querySelector("colgroup col:nth-child(2)");
        if (colgroupCol2) {
            const col = document.createElement("col");
            col.width = "6%";
            col.className = "kit-hidden-mb";
            colgroupCol2.after(col);
        }
        const theadTr = document.querySelector("thead tr");
        theadTr && (theadTr.innerHTML = '<th class="kit-hidden-mb"></th><th class="kit-hidden-mb">$pl</th><th class="kit-hidden-mb">$px</th><th>$bt</th><th class="kit-hidden-mb">$zz</th><th class="kit-hidden-mb">$fl</th><th>$rq</th>');
        const tbodyTr = document.querySelectorAll("tbody tr");
        if (tbodyTr) {
            tbodyTr.forEach((tr, i) => {
                const td2 = tr.querySelector("td:nth-child(2)");
                if (td2) {
                    const td = document.createElement("td");
                    td.textContent = orderList[i];
                    td.className = 'kit-hidden-mb';
                    td2.after(td);
                }
            });
        }
    }
})();
</script>
EOD;
            }
        }
    }
}