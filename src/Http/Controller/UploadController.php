<?php

declare(strict_types=1);

namespace Antmin\Http\Controller;

use Antmin\Contract\FileStorageInterface;
use Antmin\Exceptions\CommonException;
use Antmin\Http\Repository\AccountRepository;
use Antmin\Support\HyperfContext;
use Ramsey\Uuid\Uuid;
use Psr\Http\Message\ResponseInterface;

class UploadController extends AbstractController
{
    private const ACTIONS = [
        'imageUpload',
        'videoUpload',
        'fileUpload',
    ];

    private const ALLOWED_EXTENSIONS = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'svg'],
        'file' => ['xlsx', 'xls', 'docx', 'doc', 'csv', 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'svg'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
    ];

    private const SIZE_LIMITS = [
        'image' => 2,
        'file' => 10,
        'video' => 200,
    ];

    private const EDITOR_CONFIG = <<<'JSON'
{
    "imageActionName": "uploadimage",
    "imageFieldName": "upfile",
    "imageMaxSize": 2048000,
    "imageAllowFiles": [".png", ".jpg", ".jpeg", ".gif", ".bmp"],
    "imageCompressEnable": true,
    "imageCompressBorder": 1600,
    "imageInsertAlign": "none",
    "imageUrlPrefix": "",
    "imagePathFormat": "/upload/images/{yyyy}{mm}{dd}/{time}{rand:6}",
    "scrawlActionName": "uploadscrawl",
    "scrawlFieldName": "upfile",
    "scrawlPathFormat": "/ueditor/php/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}",
    "scrawlMaxSize": 2048000,
    "scrawlUrlPrefix": "",
    "scrawlInsertAlign": "none",
    "snapscreenActionName": "uploadimage",
    "snapscreenPathFormat": "/ueditor/php/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}",
    "snapscreenUrlPrefix": "",
    "snapscreenInsertAlign": "none",
    "catcherLocalDomain": ["127.0.0.1", "localhost", "img.baidu.com"],
    "catcherActionName": "catchimage",
    "catcherFieldName": "source",
    "catcherPathFormat": "/ueditor/php/upload/image/{yyyy}{mm}{dd}/{time}{rand:6}",
    "catcherUrlPrefix": "",
    "catcherMaxSize": 2048000,
    "catcherAllowFiles": [".png", ".jpg", ".jpeg", ".gif", ".bmp"],
    "videoActionName": "uploadvideo",
    "videoFieldName": "upfile",
    "videoPathFormat": "/ueditor/php/upload/video/{yyyy}{mm}{dd}/{time}{rand:6}",
    "videoUrlPrefix": "",
    "videoMaxSize": 102400000,
    "videoAllowFiles": [".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"],
    "fileActionName": "uploadfile",
    "fileFieldName": "upfile",
    "filePathFormat": "/ueditor/php/upload/file/{yyyy}{mm}{dd}/{time}{rand:6}",
    "fileUrlPrefix": "",
    "fileMaxSize": 51200000,
    "fileAllowFiles": [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid", ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"],
    "imageManagerActionName": "listimage",
    "imageManagerListPath": "/ueditor/php/upload/image/",
    "imageManagerListSize": 20,
    "imageManagerUrlPrefix": "",
    "imageManagerInsertAlign": "none",
    "imageManagerAllowFiles": [".png", ".jpg", ".jpeg", ".gif", ".bmp"],
    "fileManagerActionName": "listfile",
    "fileManagerListPath": "/ueditor/php/upload/file/",
    "fileManagerUrlPrefix": "",
    "fileManagerListSize": 20,
    "fileManagerAllowFiles": [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid", ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"]
}
JSON;

    public function __construct(
        private readonly AccountRepository $accountRepo,
        private readonly HyperfContext $context,
        private readonly FileStorageInterface $fileStorage,
        \Antmin\Common\Base $base,
        \Psr\Http\Message\ServerRequestInterface $request,
        \Hyperf\Validation\Contract\ValidatorFactoryInterface $validatorFactory,
    ) {
        parent::__construct($base, $request, $validatorFactory);
    }

    public function operate(): ResponseInterface
    {
        $action = $this->resolveOperateAction(self::ACTIONS);

        return $this->{$action}();
    }

    protected function imageUpload(): ResponseInterface
    {
        return $this->handleFileUpload('image', 'file');
    }

    protected function videoUpload(): ResponseInterface
    {
        return $this->handleFileUpload('video', 'file');
    }

    protected function fileUpload(): ResponseInterface
    {
        return $this->handleFileUpload('file', 'file');
    }

    public function editorUpload(): ResponseInterface
    {
        $input = $this->input();
        $action = (string) ($input['action'] ?? '');
        if ($action === '') {
            throw new CommonException('action不能为空');
        }

        if (strtoupper($this->request->getMethod()) === 'GET' && $action === 'config') {
            return $this->context->response()->json(
                json_decode(self::EDITOR_CONFIG, true) ?? []
            );
        }

        $uploadedFiles = $this->request->getUploadedFiles();
        if (! isset($uploadedFiles['upfile'])) {
            throw new CommonException('不存在upfile');
        }

        $file = $uploadedFiles['upfile'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $this->context->response()->json([
                'state' => 'ERROR',
                'msg' => '上传失败',
            ]);
        }

        $fileSize = (int) $file->getSize();
        $date = date('Ymd');
        $originalName = (string) $file->getClientFilename();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fileName = Uuid::uuid4()->toString() . '.' . $extension;
        $savePath = $this->fileStorage->storeUploadedFile('upload/file/' . $date, $fileName, $file);

        return $this->context->response()->json([
            'state' => 'SUCCESS',
            'url' => $this->fileStorage->fullUrl($savePath),
            'title' => $fileName,
            'original' => $originalName,
            'type' => $extension,
            'size' => $this->base->formatSizeUnits($fileSize),
        ]);
    }

    private function handleFileUpload(string $fileType, string $fileKey): ResponseInterface
    {
        $uploadedFiles = $this->request->getUploadedFiles();
        if (! isset($uploadedFiles[$fileKey])) {
            throw new CommonException($fileKey . '不存在');
        }

        $file = $uploadedFiles[$fileKey];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new CommonException('文件无效');
        }

        $fileSize = (int) $file->getSize();
        $originalName = (string) $file->getClientFilename();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $maxSize = self::SIZE_LIMITS[$fileType] ?? 2;
        if ($fileSize > 1024 * 1024 * $maxSize) {
            throw new CommonException('超过最大允许上传大小 ' . $maxSize . 'MB');
        }

        $allowedExtensions = self::ALLOWED_EXTENSIONS[$fileType] ?? [];
        if (! in_array($extension, $allowedExtensions, true)) {
            throw new CommonException('不支持的文件格式: ' . $extension);
        }

        $fileName = Uuid::uuid4()->toString() . '.' . $extension;
        $relativePath = $this->fileStorage->storeUploadedFile('upload/' . $fileType . '/' . date('Ymd'), $fileName, $file);
        $filePath = $this->fileStorage->url($relativePath);

        $responseData = [
            'filePath' => $filePath,
            'fileUrl' => $this->fileStorage->fullUrl($relativePath),
            'size' => $this->base->formatSizeUnits($fileSize),
            'originalName' => $originalName,
            'extension' => $extension,
        ];

        $input = $this->input();
        if (($input['type'] ?? '') === 'avatar') {
            $accountId = (int) ($input['accountId'] ?? $this->request->getAttribute('accountId') ?? 0);
            if ($accountId > 0) {
                $this->accountRepo->updateAvatar($filePath, $accountId);
            }
        }

        return $this->success('文件上传成功', $responseData);
    }
}
