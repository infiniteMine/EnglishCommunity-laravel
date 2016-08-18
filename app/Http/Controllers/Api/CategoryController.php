<?php

namespace App\Http\Controllers\Api;

use App\Http\Model\Category;
use Illuminate\Http\Request;
use App\Http\Model\Video;
use App\Http\Model\VideoInfo;

use App\Http\Requests;
use Illuminate\Support\Facades\Validator;

class CategoryController extends BaseController
{
    /**
     * @api {get} /getVideoInfosList.api 视频信息列表
     * @apiDescription 根据分类id查询视频信息列表
     * @apiGroup Category
     * @apiPermission none
     * @apiParam {Number} category_id  分类id
     * @apiParam {Number} [page]  页码
     * @apiParam {Number} [count]  每页数量,默认10条
     * @apiParam {Number} [recommend]  是否返回推荐的视频 1是 0否
     * @apiVersion 0.0.1
     * @apiSuccessExample {json} Success-Response:
     *       {
     *       }
     * @apiErrorExample {json} Error-Response:
     *     {
     *           "status": "error",
     *           "code": 404,
     *           "message": "查询视频列表失败"
     *      }
     */
    public function getVideoInfosList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required'],
            'recommend' => ['in:0,1']
        ], [
            'category_id.required' => 'category_id不能为空',
            'recommend.in' => 'recommend只能传0、1'
        ]);
        if ($validator->fails()) {
            return $this->respondWithFailedValidation($validator);
        }

        $videoInfos = null;
        if ($request->category_id == 0) {
            // 分类id为0则查询所有分类
            $videoInfos = VideoInfo::where('recommend', '>=', (isset($request->recommend) && $request->recommend == 1) ? 1 : 0)
                ->orderBy('id', 'desc')
                ->paginate(isset($request->count) ? $request->count : 10);
            if (! count($videoInfos)) {
                return $this->respondWithErrors('没有查到视频信息');
            }
        } else {
            // 否则查询指定分类
            $category = Category::find($request->category_id);
            $category->increment('view');
            if (! isset($category)) {
                return $this->respondWithErrors('category_id错误', 400);
            }
            $videoInfos = VideoInfo::where('category_id', $request->category_id)
                ->where('recommend', '>=', (isset($request->recommend) && $request->recommend == 1) ? 1 : 0)
                ->orderBy('id', 'desc')
                ->paginate(isset($request->count) ? $request->count : 10);
            if (! count($videoInfos)) {
                return $this->respondWithErrors('没有查到视频信息');
            }
        }

        $result = null;
        $data = $videoInfos->all();
        foreach ($data as $key => $videoInfo) {
            $result[$key]['id'] = $videoInfo->id;
            $result[$key]['title'] = $videoInfo->title;
            $result[$key]['cover'] = url($videoInfo->photo);
            $result[$key]['view'] = $videoInfo->view;
            $result[$key]['teacherName'] = $videoInfo->teacher;
            $result[$key]['videoType'] = $videoInfo->type;
            $result[$key]['recommended'] = $videoInfo->recommend;
            $result[$key]['videoCount'] = Video::where('video_info_id', $videoInfo->id)->count();
        }

        return $this->respondWithSuccess([
            'pageInfo' => [
                'total' => $videoInfos->total(),
                'currentPage' => $videoInfos->currentPage(),
            ],
            'data' => $result
        ], '查询指定分类视频列表成功');

    }

    /**
     * @api {get} /getAllCategories.api 所有分类信息
     * @apiDescription 获取所有分类信息
     * @apiGroup Category
     * @apiPermission none
     * @apiParam {Number} [have_data]  是否返回带数据的分类信息数据, 1有 0无
     * @apiParam {Number} [count]  每个分类信息返回多少条视频数据
     * @apiVersion 0.0.1
     * @apiSuccessExample {json} Success-Response:
     *       {
     *       }
     * @apiErrorExample {json} Error-Response:
     *     {
     *           "status": "error",
     *           "code": 404,
     *           "message": "查询分类列表失败"
     *      }
     */
    public function getCategoryies(Request $request)
    {
        $categories = Category::orderBy('order', 'asc')->get(['id', 'name', 'alias', 'view']);
        if (count($categories) == 0) {
            return $this->respondWithErrors('没有查到任何分类');
        }

        // 如果不带数据,则直接返回分类信息
        if (! isset($request->have_data)) {
            return $this->respondWithSuccess($categories, '查询分类列表成功');
        }

        // 如果带数据,则返回带数据的分类信息
        foreach ($categories as $key => $category) {

            $videoInfos = VideoInfo::where('category_id', $category->id)
                ->orderBy('id', 'desc')
                ->take(isset($request->count) ? $request->count : 4)
                ->get();
            $result = null;
            foreach ($videoInfos as $k => $videoInfo) {
                $result[$k]['id'] = $videoInfo->id;
                $result[$k]['title'] = $videoInfo->title;
                $result[$k]['cover'] = url($videoInfo->photo);
                $result[$k]['view'] = $videoInfo->view;
                $result[$k]['teacherName'] = $videoInfo->teacher;
                $result[$k]['videoType'] = $videoInfo->type;
                $result[$k]['recommended'] = $videoInfo->recommend;
                $result[$key]['videoCount'] = Video::where('video_info_id', $videoInfo->id)->count();
            }

            $categories[$key]['videoInfoList'] = $result;
        }
        
        return $this->respondWithSuccess($categories, '查询分类列表成功');
    }

}
