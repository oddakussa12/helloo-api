<?php
namespace App\Custom\NEIm;

class NEConstants {
    public const create_acc_id = "https://api.netease.im/nimserver/user/create.action";
    public const update_acc_id = "https://api.netease.im/nimserver/user/update.action";
    public const refresh_acc_id = "https://api.netease.im/nimserver/user/refreshToken.action";
    public const block_acc_id = "https://api.netease.im/nimserver/user/block.action";
    public const unblock_acc_id = "https://api.netease.im/nimserver/user/unblock.action";

    public const update_acc_id_info = "https://api.netease.im/nimserver/user/updateUinfo.action";
    public const get_acc_id_infos = "https://api.netease.im/nimserver/user/getUinfos.actio";

    public const set_don_nop = "https://api.netease.im/nimserver/user/setDonnop.action";

    public const add_friends = "https://api.netease.im/nimserver/friend/add.action";
    public const update_friends = "https://api.netease.im/nimserver/friend/update.action";
    public const delete_friends = "https://api.netease.im/nimserver/friend/delete.action";
    public const get_friends = "https://api.netease.im/nimserver/friend/get.action";
    public const quiet_black_friends = "https://api.netease.im/nimserver/user/setSpecialRelation.action";
    public const quiet_black_friends_list = "https://api.netease.im/nimserver/user/listBlackAndMuteList.action";
    ############################################################single chat
    public const send_msg = "https://api.netease.im/nimserver/msg/sendMsg.action";
    public const batch_send_p2p_msg = "https://api.netease.im/nimserver/msg/sendBatchMsg.action";
    public const self_define_sys_notify = "https://api.netease.im/nimserver/msg/sendAttachMsg.action";
    public const batch_p2p_sys_notify = "https://api.netease.im/nimserver/msg/sendBatchAttachMsg.action";
    public const file_upload = "https://api.netease.im/nimserver/msg/upload.action";
    public const file_upload_multi = "https://api.netease.im/nimserver/msg/fileUpload.action";
    public const recall = "https://api.netease.im/nimserver/msg/recall.action";
    public const broadcast = "https://api.netease.im/nimserver/msg/broadcastMsg.action";
    ############################################################group chat
    public const fund_group = "https://api.netease.im/nimserver/team/create.action";
    public const add_sb_group = "https://api.netease.im/nimserver/team/add.action";
    public const kick_out_group = "https://api.netease.im/nimserver/team/kick.action";
    public const remove_group = "https://api.netease.im/nimserver/team/remove.action";
    public const update_group_info = "https://api.netease.im/nimserver/team/update.action";
    public const query_group_info = "https://api.netease.im/nimserver/team/query.action";
    public const query_group_detail = "https://api.netease.im/nimserver/team/queryDetail.action";
    public const change_owner = "https://api.netease.im/nimserver/team/changeOwner.action";
    public const add_manager = "https://api.netease.im/nimserver/team/addManager.action";
    public const remove_manager = "https://api.netease.im/nimserver/team/removeManager.action";
    public const joined_teams = "https://api.netease.im/nimserver/team/joinTeams.action";
    public const update_team_nick = "https://api.netease.im/nimserver/team/updateTeamNick.action";
    public const mute_team = "https://api.netease.im/nimserver/team/muteTeam.action";
    public const shut_up = "https://api.netease.im/nimserver/team/muteTlist.action";
    public const quit_team = "https://api.netease.im/nimserver/team/leave.action";
    public const shut_up_all_team = "https://api.netease.im/nimserver/team/muteTlistAll.action";
    public const shut_up_list = "https://api.netease.im/nimserver/team/listTeamMute.action";
    ##############################################################chat root
    public const fund_chat_root = "https://api.netease.im/nimserver/chatroom/create.action";
    public const query_chat_root_info = "https://api.netease.im/nimserver/chatroom/get.action";
    public const batch_query_chat_root_info = "https://api.netease.im/nimserver/chatroom/getBatch.action";
    public const update_chat_root_info = "https://api.netease.im/nimserver/chatroom/update.action";
    public const toggle_close_stat = "https://api.netease.im/nimserver/chatroom/toggleCloseStat.action";
    public const set_member_role = "https://api.netease.im/nimserver/chatroom/setMemberRole.action";
    public const request_address = "https://api.netease.im/nimserver/chatroom/requestAddr.action";
    public const send_chat_root_msg = "https://api.netease.im/nimserver/chatroom/sendMsg.action";
    public const add_robot = "https://api.netease.im/nimserver/chatroom/addRobot.action";
    public const remove_robot = "https://api.netease.im/nimserver/chatroom/removeRobot.action";
    public const temporary_shut_up = "https://api.netease.im/nimserver/chatroom/temporaryMute.action";
    public const queue_offer = "https://api.netease.im/nimserver/chatroom/queueOffer.action";
    public const queue_poll = "https://api.netease.im/nimserver/chatroom/queuePoll.action";
    public const queue_list = "https://api.netease.im/nimserver/chatroom/queueList.action";
    public const queue_drop = "https://api.netease.im/nimserver/chatroom/queueDrop.action";
    public const queue_init = "https://api.netease.im/nimserver/chatroom/queueInit.action";
    public const shut_up_one_room = "h`ttps://api.netease.im/nimserver/chatroom/muteRoom.action";
    public const top_n = "https://api.netease.im/nimserver/stats/chatroom/topn.action";
    public const members_by_page = "https://api.netease.im/nimserver/chatroom/membersByPage.action";
    public const query_members_online = "https://api.netease.im/nimserver/chatroom/queryMembers.action";
    public const update_my_room_role = "https://api.netease.im/nimserver/chatroom/updateMyRoomRole.action";
    ##################################################################history
    // 单聊云端历史消息查询
    public const history = "https://api.netease.im/nimserver/history/querySessionMsg.action";
    // 群聊云端历史消息查询
    public const team_query_history = "https://api.netease.im/nimserver/history/queryTeamMsg.action";
    // 聊天室云端历史消息查询
    public const root_query_history = "https://api.netease.im/nimserver/history/queryChatroomMsg.action";
    // 删除聊天室云端历史消息
    public const delete_root_history = "https://api.netease.im/nimserver/chatroom/deleteHistoryMessage.action";
    // 用户登录登出事件记录查询
    public const query_user_event = "https://api.netease.im/nimserver/history/queryUserEvents.action";
    // 删除音视频/白板服务器录制文件
    public const delete_media_file = "https://api.netease.im/nimserver/history/deleteMediaFile.action";
    // 批量查询广播消息
    public const query_broadcast_msg = "https://api.netease.im/nimserver/history/queryBroadcastMsg.action";
    // 查询单条广播消息
    public const query_broadcast_msg_by_id = "https://api.netease.im/nimserver/history/queryBroadcastMsgById.action";
    ###################################################################online status
    // 订阅在线状态事件
    public const add_subscription = "https://api.netease.im/nimserver/event/subscribe/add.action";
    // 取消在线状态事件订阅
    public const cancel_subscription = "https://api.netease.im/nimserver/event/subscribe/delete.action";
    // 取消全部在线状态事件订阅
    public const cancel_all_subscription = "https://api.netease.im/nimserver/event/subscribe/batchdel.action";
    // 查询在线状态事件订阅关系
    public const subscription_event_query = "https://api.netease.im/nimserver/event/subscribe/query.action";
    ###################################################################msg copy
}