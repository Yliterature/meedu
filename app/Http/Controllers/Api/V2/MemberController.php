<?php

/*
 * This file is part of the Qsnh/meedu.
 *
 * (c) XiaoTeng <616896861@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Constant\ApiV2Constant;
use App\Businesses\BusinessState;
use App\Exceptions\ApiV2Exception;
use Illuminate\Support\Facades\Auth;
use App\Services\Member\Services\RoleService;
use App\Services\Member\Services\UserService;
use App\Services\Order\Services\OrderService;
use App\Services\Course\Services\VideoService;
use App\Services\Course\Services\CourseService;
use App\Http\Requests\ApiV2\AvatarChangeRequest;
use App\Services\Order\Services\PromoCodeService;
use App\Http\Requests\ApiV2\PasswordChangeRequest;
use App\Services\Member\Services\SocialiteService;
use App\Services\Member\Interfaces\RoleServiceInterface;
use App\Services\Member\Interfaces\UserServiceInterface;
use App\Services\Order\Interfaces\OrderServiceInterface;
use App\Services\Course\Interfaces\VideoServiceInterface;
use App\Services\Course\Interfaces\CourseServiceInterface;
use App\Services\Member\Services\UserInviteBalanceService;
use App\Services\Order\Interfaces\PromoCodeServiceInterface;
use App\Services\Member\Interfaces\SocialiteServiceInterface;
use App\Services\Member\Interfaces\UserInviteBalanceServiceInterface;

/**
 * @OpenApi\Annotations\Schemas(
 *     @OA\Schema(
 *         schema="UserRole",
 *         type="object",
 *         title="用户套餐记录",
 *         @OA\Property(property="user_id",type="integer",description="用户id"),
 *         @OA\Property(property="role_id",type="integer",description="套餐id"),
 *         @OA\Property(property="charge",type="integer",description="购买时价格"),
 *         @OA\Property(property="started_at",type="string",description="开始时间"),
 *         @OA\Property(property="expired_at",type="string",description="结束时间"),
 *         @OA\Property(property="role",type="object",description="套餐",ref="#/components/schemas/Role"),
 *     ),
 *     @OA\Schema(
 *         schema="UserCourse",
 *         type="object",
 *         title="用户课程记录",
 *         @OA\Property(property="user_id",type="integer",description="用户id"),
 *         @OA\Property(property="course_id",type="integer",description="课程id"),
 *         @OA\Property(property="charge",type="integer",description="购买时价格"),
 *         @OA\Property(property="created_at",type="string",description="购买时间"),
 *     ),
 *     @OA\Schema(
 *         schema="UserVideo",
 *         type="object",
 *         title="用户视频记录",
 *         @OA\Property(property="user_id",type="integer",description="用户id"),
 *         @OA\Property(property="video_id",type="integer",description="视频id"),
 *         @OA\Property(property="charge",type="integer",description="购买时价格"),
 *         @OA\Property(property="created_at",type="string",description="购买时间"),
 *     ),
 *     @OA\Schema(
 *         schema="Order",
 *         type="object",
 *         title="订单",
 *         @OA\Property(property="user_id",type="integer",description="用户id"),
 *         @OA\Property(property="order_id",type="string",description="订单编号"),
 *         @OA\Property(property="charge",type="integer",description="订单总价"),
 *         @OA\Property(property="status_text",type="string",description="订单状态"),
 *         @OA\Property(property="payment_text",type="string",description="支付渠道"),
 *         @OA\Property(property="payment_method",type="string",description="支付渠道的支付方式"),
 *         @OA\Property(property="continue_pay",type="integer",description="是否可以继续支付"),
 *         @OA\Property(property="created_at",type="string",description="时间"),
 *         @OA\Property(property="goods",type="array",description="订单商品",@OA\Items(ref="#/components/schemas/OrderGoods")),
 *     ),
 *     @OA\Schema(
 *         schema="UserInviteBalanceRecord",
 *         type="object",
 *         title="邀请余额明细",
 *         @OA\Property(property="total",type="integer",description="金额"),
 *         @OA\Property(property="desc",type="string",description="说明"),
 *         @OA\Property(property="created_at",type="integer",description="时间"),
 *     ),
 *     @OA\Schema(
 *         schema="PromoCode",
 *         type="object",
 *         title="优惠码",
 *         @OA\Property(property="code",type="integer",description="优惠码"),
 *         @OA\Property(property="expired_at",type="string",description="过期时间"),
 *         @OA\Property(property="invite_user_reward",type="integer",description="邀请人奖励"),
 *         @OA\Property(property="invited_user_reward",type="integer",description="被邀请人奖励"),
 *     ),
 *     @OA\Schema(
 *         schema="Notification",
 *         type="object",
 *         title="消息",
 *         @OA\Property(property="message",type="string",description="消息内容"),
 *     ),
 * )
 */
class MemberController extends BaseController
{
    /**
     * @var UserService
     */
    protected $userService;
    /**
     * @var CourseService
     */
    protected $courseService;
    /**
     * @var VideoService
     */
    protected $videoService;
    /**
     * @var RoleService
     */
    protected $roleService;
    /**
     * @var OrderService
     */
    protected $orderService;
    /**
     * @var SocialiteService
     */
    protected $socialiteService;
    /**
     * @var UserInviteBalanceService
     */
    protected $userInviteBalanceService;
    /**
     * @var PromoCodeService
     */
    protected $promoCodeService;
    protected $businessState;

    public function __construct(
        UserServiceInterface $userService,
        CourseServiceInterface $courseService,
        VideoServiceInterface $videoService,
        RoleServiceInterface $roleService,
        OrderServiceInterface $orderService,
        SocialiteServiceInterface $socialiteService,
        UserInviteBalanceServiceInterface $userInviteBalanceService,
        PromoCodeServiceInterface $promoCodeService,
        BusinessState $businessState
    ) {
        $this->userService = $userService;
        $this->courseService = $courseService;
        $this->videoService = $videoService;
        $this->roleService = $roleService;
        $this->orderService = $orderService;
        $this->socialiteService = $socialiteService;
        $this->userInviteBalanceService = $userInviteBalanceService;
        $this->promoCodeService = $promoCodeService;
        $this->businessState = $businessState;
    }

    /**
     * @OA\Get(
     *     path="/member/detail",
     *     summary="用户信息",
     *     security={{"bearer":{}}},
     *     tags={"用户"},
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description="",ref="#/components/schemas/User"),
     *         )
     *     )
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $user = $this->userService->find(Auth::guard($this->guard)->id());
        $user = arr1_clear($user, ApiV2Constant::MODEL_MEMBER_FIELD);

        return $this->data($user);
    }

    /**
     * @OA\Post(
     *     path="/member/password",
     *     summary="修改密码",
     *     tags={"用户"},
     *     @OA\RequestBody(description="",@OA\JsonContent(
     *         @OA\Property(property="mobile",description="手机号",type="string"),
     *         @OA\Property(property="mobile_code",description="手机短信验证码",type="string"),
     *         @OA\Property(property="password",description="密码",type="string"),
     *     )),
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description=""),
     *         )
     *     )
     * )
     *
     * @param PasswordChangeRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws ApiV2Exception
     */
    public function passwordChange(PasswordChangeRequest $request)
    {
        $this->mobileCodeCheck();
        ['password' => $password, 'mobile' => $mobile] = $request->filldata();
        $user = $this->userService->find($this->id());
        if ($user['mobile'] != $mobile) {
            return $this->error(ApiV2Constant::MOBILE_CODE_ERROR);
        }
        $this->userService->changePassword($this->id(), $password);

        return $this->success();
    }

    /**
     * @OA\Post(
     *     path="/member/avatar",
     *     summary="修改头像",
     *     tags={"用户"},
     *     @OA\RequestBody(description="",@OA\JsonContent(
     *         @OA\Property(property="file",description="图片文件",type="object"),
     *     )),
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description=""),
     *         )
     *     )
     * )
     *
     * @param AvatarChangeRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function avatarChange(AvatarChangeRequest $request)
    {
        ['url' => $url] = $request->filldata();
        $this->userService->updateAvatar($this->id(), $url);

        return $this->success();
    }

    /**
     * @OA\Get(
     *     path="/member/roles",
     *     summary="用户订购套餐信息",
     *     tags={"用户"},
     *     @OA\Parameter(in="query",name="page",description="页码",required=false,@OA\Schema(type="integer")),
     *     @OA\Parameter(in="query",name="page_size",description="每页数量",required=false,@OA\Schema(type="integer")),
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description="",
     *                 @OA\Property(property="total",type="integer",description="总数"),
     *                 @OA\Property(property="list",type="array",description="列表",@OA\Items(ref="#/components/schemas/UserRole")),
     *             ),
     *         )
     *     )
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function roles(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 5);
        [
            'total' => $total,
            'list' => $list,
        ] = $this->roleService->userRolePaginate($page, $pageSize);
        $records = $this->paginator($list, $total, $page, $pageSize);

        return $this->data($records);
    }

    /**
     * @OA\Get(
     *     path="/member/messages",
     *     summary="用户消息",
     *     tags={"用户"},
     *     @OA\Parameter(in="query",name="page",description="页码",required=false,@OA\Schema(type="integer")),
     *     @OA\Parameter(in="query",name="page_size",description="每页数量",required=false,@OA\Schema(type="integer")),
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description="",
     *                 @OA\Property(property="total",type="integer",description="总数"),
     *                 @OA\Property(property="list",type="array",description="列表",@OA\Items(ref="#/components/schemas/Notification")),
     *             ),
     *         )
     *     )
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function messages(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 5);
        [
            'total' => $total,
            'list' => $list,
        ] = $this->userService->messagePaginate($page, $pageSize);
        $messages = $this->paginator($list, $total, $page, $pageSize);

        return $this->data($messages);
    }

    /**
     * @OA\Get(
     *     path="/member/courses",
     *     summary="用户课程",
     *     tags={"用户"},
     *     @OA\Parameter(in="query",name="page",description="页码",required=false,@OA\Schema(type="integer")),
     *     @OA\Parameter(in="query",name="page_size",description="每页数量",required=false,@OA\Schema(type="integer")),
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description="",
     *                 @OA\Property(property="total",type="integer",description="总数"),
     *                 @OA\Property(property="data",type="array",description="列表",@OA\Items(ref="#/components/schemas/UserCourse")),
     *                 @OA\Property(property="courses",type="array",description="课程",@OA\Items(ref="#/components/schemas/Course")),
     *             ),
     *         )
     *     )
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function courses(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 5);

        [
            'total' => $total,
            'list' => $list,
        ] = $this->userService->getUserBuyCourses($page, $pageSize);
        $records = $this->paginator($list, $total, $page, $pageSize);
        // 读取关联课程
        $courses = $this->courseService->getList(array_column($list, 'course_id'));
        $courses = arr2_clear($courses, ApiV2Constant::MODEL_COURSE_FIELD);
        $courses = array_column($courses, null, 'id');
        $records['courses'] = $courses;

        return $this->data($records);
    }

    /**
     * @OA\Get(
     *     path="/member/videos",
     *     summary="用户课程",
     *     tags={"用户"},
     *     @OA\Parameter(in="query",name="page",description="页码",required=false,@OA\Schema(type="integer")),
     *     @OA\Parameter(in="query",name="page_size",description="每页数量",required=false,@OA\Schema(type="integer")),
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description="",
     *                 @OA\Property(property="total",type="integer",description="总数"),
     *                 @OA\Property(property="data",type="array",description="列表",@OA\Items(ref="#/components/schemas/UserVideo")),
     *                 @OA\Property(property="videos",type="array",description="视频",@OA\Items(ref="#/components/schemas/Video")),
     *             ),
     *         )
     *     )
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function videos(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 5);

        [
            'total' => $total,
            'list' => $list,
        ] = $this->userService->getUserBuyVideos($page, $pageSize);
        $records = $this->paginator($list, $total, $page, $pageSize);

        // 读取关联视频
        $videos = $this->videoService->getList(array_column($list, 'video_id'));
        $videos = arr2_clear($videos, ApiV2Constant::MODEL_VIDEO_FIELD);
        $videos = array_column($videos, null, 'id');
        $records['videos'] = $videos;

        return $this->data($records);
    }

    /**
     * @OA\Get(
     *     path="/member/orders",
     *     summary="用户订单",
     *     tags={"用户"},
     *     @OA\Parameter(in="query",name="page",description="页码",required=false,@OA\Schema(type="integer")),
     *     @OA\Parameter(in="query",name="page_size",description="每页数量",required=false,@OA\Schema(type="integer")),
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description="",
     *                 @OA\Property(property="total",type="integer",description="总数"),
     *                 @OA\Property(property="data",type="array",description="列表",@OA\Items(ref="#/components/schemas/Order")),
     *             ),
     *         )
     *     )
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orders(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 5);
        [
            'total' => $total,
            'list' => $list,
        ] = $this->orderService->userOrdersPaginate($page, $pageSize);
        $list = arr2_clear($list, ApiV2Constant::MODEL_ORDER_FIELD);
        foreach ($list as $key => $val) {
            $list[$key]['goods'] = arr2_clear($val['goods'], ApiV2Constant::MODEL_ORDER_GOODS_FIELD);
        }
        $orders = $this->paginator($list, $total, $page, $pageSize);

        return $this->data($orders);
    }

    /**
     * @OA\Get(
     *     path="/member/inviteBalanceRecrods",
     *     summary="用户邀请余额明细",
     *     tags={"用户"},
     *     @OA\Parameter(in="query",name="page",description="页码",required=false,@OA\Schema(type="integer")),
     *     @OA\Parameter(in="query",name="page_size",description="每页数量",required=false,@OA\Schema(type="integer")),
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description="",
     *                 @OA\Property(property="total",type="integer",description="总数"),
     *                 @OA\Property(property="data",type="array",description="列表",@OA\Items(ref="#/components/schemas/UserInviteBalanceRecord")),
     *             ),
     *         )
     *     )
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteBalanceRecords(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 5);
        [
            'total' => $total,
            'list' => $list,
        ] = $this->userInviteBalanceService->simplePaginate($page, $pageSize);
        $records = $this->paginator($list, $total, $page, $pageSize);

        return $this->data($records);
    }

    /**
     * @OA\Get(
     *     path="/member/promoCode",
     *     summary="用户邀请码",
     *     tags={"用户"},
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description="",ref="#/components/schemas/PromoCode"),
     *         )
     *     )
     * )
     * @return \Illuminate\Http\JsonResponse
     */
    public function promoCode()
    {
        $promoCode = $this->promoCodeService->getCurrentUser();
        return $this->data($promoCode);
    }

    /**
     * @OA\Post(
     *     path="/member/promoCode",
     *     summary="生成用户邀请码",
     *     tags={"用户"},
     *     @OA\Response(
     *         description="",response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="code",type="integer",description="状态码"),
     *             @OA\Property(property="message",type="string",description="消息"),
     *             @OA\Property(property="data",type="object",description=""),
     *         )
     *     )
     * )
     * @return \Illuminate\Http\JsonResponse
     */
    public function generatePromoCode()
    {
        if (!$this->businessState->canGenerateInviteCode($this->user())) {
            return $this->error(__('current user cant generate promo code'));
        }
        $this->promoCodeService->userCreate($this->user());
        return $this->success();
    }
}
