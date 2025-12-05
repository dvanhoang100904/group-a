import { ref } from 'vue';
import axios from 'axios';
import { validateFolderId } from '../utils/sanitizeUtils';

export function useShareManagement() {
    const shareModalData = ref({
        emailsInput: '',
        permission: 'view',
        loading: false,
        sharedUsers: [],
        emailError: ''
    });

    const validateEmails = (emailsInput) => {
        const emails = emailsInput.split(',')
            .map(email => email.trim())
            .filter(email => email);

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const invalidEmails = emails.filter(email => !emailRegex.test(email));

        if (invalidEmails.length > 0) {
            return {
                valid: false,
                error: `Email không hợp lệ: ${invalidEmails.join(', ')}`,
                validEmails: []
            };
        }

        return {
            valid: true,
            error: '',
            validEmails: emails
        };
    };

    const loadSharedUsers = async (folderId) => {
        try {
            const validId = validateFolderId(folderId);
            const response = await axios.get(`/api/folders/${validId}/shared-users`);

            if (response.data.success) {
                shareModalData.value.sharedUsers = response.data.data || [];
            }
        } catch (error) {
            console.error('Lỗi khi tải danh sách chia sẻ:', error);
            throw error;
        }
    };

    const shareFolder = async (folderId, emails, permission) => {
        shareModalData.value.loading = true;
        shareModalData.value.emailError = '';

        try {
            const validId = validateFolderId(folderId);
            const response = await axios.post(`/api/folders/${validId}/share`, {
                emails,
                permission
            });

            if (response.data.success) {
                await loadSharedUsers(folderId);
                return {
                    success: true,
                    message: response.data.message
                };
            } else {
                throw new Error(response.data.message || 'Lỗi khi chia sẻ folder');
            }
        } catch (error) {
            const message = error.response?.data?.message || 'Lỗi khi chia sẻ folder';
            shareModalData.value.emailError = message;
            throw error;
        } finally {
            shareModalData.value.loading = false;
        }
    };

    const unshareUser = async (folderId, userId) => {
        try {
            const validId = validateFolderId(folderId);
            const response = await axios.post(`/api/folders/${validId}/unshare`, {
                user_ids: [userId]
            });

            if (response.data.success) {
                await loadSharedUsers(folderId);
                return {
                    success: true,
                    message: 'Hủy chia sẻ thành công'
                };
            } else {
                throw new Error('Lỗi khi hủy chia sẻ');
            }
        } catch (error) {
            console.error('Lỗi khi hủy chia sẻ:', error);
            throw error;
        }
    };

    return {
        shareModalData,
        validateEmails,
        loadSharedUsers,
        shareFolder,
        unshareUser
    };
}